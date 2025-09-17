<?php
/**
 * AvisDtoBuilder
 *
 * Objectif: centraliser la transformation d'un document d'avis (Mongo + infos SQL incident éventuelles)
 * vers un tableau enrichi destiné aux réponses API.
 */
class AvisDtoBuilder
{
    /**
     * Construit l'objet avis pour la liste de modération (en attente de traitement).
     * @param mixed $doc Document MongoDB brut (array ou MongoDB\Model\BSONDocument).
     * @param array|null $incident Ligne SQL de l'incident lié ou null si pas d'incident.
     * @return array Tableau fusionnant legacy + structure normalisée enrichie.
     */
    public function buildPending($doc, ?array $incident): array
    {
        // Normaliser en array si BSONDocument
        if ($doc instanceof \MongoDB\Model\BSONDocument) {
            $doc = $doc->getArrayCopy();
        }
        // Sécurité: si ce n'est toujours pas un array, on force un tableau vide pour éviter fatal
        if (!is_array($doc)) { $doc = []; }

        // Copie des champs legacy (on ne casse rien pour le front actuel)
        $legacy = $doc;

        // Construction auteur (id + display + email)
        $authorId = $doc['auteur_id'] ?? $doc['utilisateur_id'] ?? null;

        // Display avancé
        $authorDisplay = $doc['auteur_nom'] ?? $doc['auteur'] ?? null;
        if (!$authorDisplay) {
            $parts = [];
            if (!empty($doc['utilisateur_prenom'])) { $parts[] = $doc['utilisateur_prenom']; }
            if (!empty($doc['utilisateur_nom'])) { $parts[] = strtoupper(substr($doc['utilisateur_nom'], 0, 1)) . '.'; }
            if ($parts) {
                $authorDisplay = implode(' ', $parts);
            } elseif (!empty($doc['utilisateur_pseudo'])) {
                $authorDisplay = $doc['utilisateur_pseudo'];
            } elseif ($authorId) {
                $authorDisplay = '#' . $authorId;
            } else {
                $authorDisplay = 'Inconnu';
            }
        }

        // Email fallback (selon comment les données sont stockées)
        $authorEmail = $doc['auteur_email']
            ?? $doc['utilisateur_email']
            ?? $doc['email_utilisateur']
            ?? $doc['email']
            ?? null;

        // Incident normalisé
        $incidentNormalized = [
            'present' => $incident !== null,
            'statut' => $incident['statut'] ?? null,
            'incident_id' => $incident['id'] ?? null,
        ];

        // Actions normalisées (schema v2) => le front utilisera actions.validate / actions.refuse
        $actions = [
            'validate' => true,
            'refuse' => true,
        ];

        // Pour compat ascendante on conserve encore quelques cycles moderation.* (deprecated)
        $moderation = [ // DEPRECATED v1 legacy
            'canValidate' => $actions['validate'],
            'canRefuse' => $actions['refuse'],
        ];

        // Nouveau format enrichi (sans supprimer les autres champs)
        $enriched = [
            // v2 ajoute schema_version=2; on laissera l'endpoint forcer la valeur globale mais utile si réutilisé ailleurs
            'schema_version' => 2,
            'id' => $doc['_id'] ?? null,
            'rating' => $doc['note'] ?? null,
            'text' => $doc['commentaire'] ?? '',
            'author' => [
                'id' => $authorId,
                'display' => $authorDisplay,
                'email' => $authorEmail,
            ],
            'covoiturage_id' => $doc['covoiturage_id'] ?? null,
            'created_at' => $doc['created_at'] ?? null,
            'incident' => $incidentNormalized,
            'actions' => $actions,
            'moderation' => $moderation, // deprecated
        ];

        // Fusion simple (les nouvelles clés ne devraient pas écraser des clés legacy importantes)
        return array_merge($legacy, $enriched);
    }

    /**
     * Construit l'objet avis pour affichage public (avis validés /approved).
     * Réduit la surface (pas de bloc moderation, ni incident.present) mais garde author structuré.
     * @param mixed $doc Document MongoDB (array ou BSONDocument)
     * @return array
     */
    public function buildPublic($doc): array
    {
        if ($doc instanceof \MongoDB\Model\BSONDocument) { $doc = $doc->getArrayCopy(); }
        if (!is_array($doc)) { $doc = []; }

        $legacy = $doc; // on garde pour compat éventuelle

        $authorId = $doc['auteur_id'] ?? $doc['utilisateur_id'] ?? null;
        // Display simplifié (réutilise même heuristique)
        $authorDisplay = $doc['auteur_nom'] ?? $doc['auteur'] ?? null;
        if (!$authorDisplay) {
            $parts = [];
            if (!empty($doc['utilisateur_prenom'])) { $parts[] = $doc['utilisateur_prenom']; }
            if (!empty($doc['utilisateur_nom'])) { $parts[] = strtoupper(substr($doc['utilisateur_nom'], 0, 1)) . '.'; }
            if ($parts) {
                $authorDisplay = implode(' ', $parts);
            } elseif (!empty($doc['utilisateur_pseudo'])) {
                $authorDisplay = $doc['utilisateur_pseudo'];
            } elseif ($authorId) {
                $authorDisplay = '#' . $authorId;
            } else {
                $authorDisplay = 'Inconnu';
            }
        }
        $authorEmail = $doc['auteur_email']
            ?? $doc['utilisateur_email']
            ?? $doc['email_utilisateur']
            ?? $doc['email']
            ?? null;

        $enriched = [
            'schema_version' => 1,
            'id' => $doc['_id'] ?? null,
            'rating' => $doc['note'] ?? null,
            'text' => $doc['commentaire'] ?? '',
            'author' => [
                'id' => $authorId,
                'display' => $authorDisplay,
                'email' => $authorEmail,
            ],
            'covoiturage_id' => $doc['covoiturage_id'] ?? null,
            'created_at' => $doc['created_at'] ?? null,
            // On ne renvoie pas moderation/incident => surface publique minimale
        ];
        return array_merge($legacy, $enriched);
    }
}
