<?php
/**
 * IncidentDtoBuilder
 *
 * Objectif: centraliser la construction d'un DTO enrichi pour les incidents afin de:
 *  - Réduire la duplication de logique d'agrégation (covoiturage, participation, utilisateurs, escrow)
 *  - Introduire une structure normalisée (schema_version=1) en conservant les champs legacy (incident_id, description...)
 *  - Exposer des flags d'actions (canRelease / canRefund / canResolve) calculés simplement côté backend
 *  - Préparer l'ajout ultérieur d'éventuels champs (audit, piece jointe, etc.) sans casser le front
 */
class IncidentDtoBuilder
{
    /**
     * Construit le DTO d'un incident.
     * @param array $incident   Ligne brute de la table incident (SELECT * FROM incident)
     * @param array $covoiturage Ligne covoiturage simplifiée
     * @param array $participation Ligne participation + champs escrow joint (alias escrow_statut, montant_brut, commission)
     * @param array|null $passager Ligne utilisateur passager
     * @param array|null $chauffeur Ligne utilisateur chauffeur
     * @param string|null $resolutionMode Mode de résolution (release|refund) si connu au moment du build (sinon null)
     * @return array Legacy + nouveaux champs enrichis
     */
    public function build(
        array $incident,
        array $covoiturage,
        array $participation,
        ?array $passager,
        ?array $chauffeur,
        ?string $resolutionMode = null
    ): array {

        // Normalisation basique des IDs / valeurs numériques
        $incidentId = (int)($incident['incident_id'] ?? 0);
        $incidentStatus = $incident['statut'] ?? 'en_cours';
        $escrowStatut = $participation['escrow_statut'] ?? 'pending';

        // Calculs monétaires escrow
        $montantBrut = isset($participation['montant_brut']) ? (float)$participation['montant_brut'] : null;
        $commission = isset($participation['commission']) ? (float)$participation['commission'] : null;
        $montantChauffeur = ($montantBrut !== null && $commission !== null) ? max(0, $montantBrut - $commission) : null;

        // Display name helper
        $computeDisplay = function (?array $u): ?string {
            if (!$u) return null;
            if (!empty($u['pseudo'])) return $u['pseudo'];
            $prenom = $u['prenom'] ?? '';
            $nom = $u['nom'] ?? '';
            if ($prenom || $nom) {
                $initial = $nom ? strtoupper(substr($nom, 0, 1)).'.' : '';
                return trim($prenom.' '.$initial);
            }
            if (!empty($u['utilisateur_id'])) return '#'.$u['utilisateur_id'];
            return null;
        };

        $passagerDto = [
            'id' => (int)($passager['utilisateur_id'] ?? 0),
            'display' => $computeDisplay($passager),
            'email' => $passager['email'] ?? null,
            'pseudo' => $passager['pseudo'] ?? null,
            'prenom' => $passager['prenom'] ?? null,
            'nom' => $passager['nom'] ?? null,
        ];
        $chauffeurDto = [
            'id' => (int)($chauffeur['utilisateur_id'] ?? 0),
            'display' => $computeDisplay($chauffeur),
            'email' => $chauffeur['email'] ?? null,
            'pseudo' => $chauffeur['pseudo'] ?? null,
            'prenom' => $chauffeur['prenom'] ?? null,
            'nom' => $chauffeur['nom'] ?? null,
        ];

        // Actions disponibles (simple: tant que incident en_cours + escrow pending)
        $canResolve = $incidentStatus === 'en_cours';
        $canEscrowOps = $canResolve && $escrowStatut === 'pending';

        // Nouvelle structure actions v2 (validate/refuse remplacées par refund/release/resolve côté incidents)
        $actions = [
            'resolve' => $canResolve, // marquer incident résolu (sans action argent)
            'release' => $canEscrowOps, // libérer fonds chauffeur
            'refund'  => $canEscrowOps, // rembourser passager
            // Champs legacy conservés pour compat front existant
            'canResolve' => $canResolve,
            'canRelease' => $canEscrowOps,
            'canRefund'  => $canEscrowOps,
        ];

        $resolution = [
            'done' => $incidentStatus === 'resolu',
            'mode' => $resolutionMode, // sera non null côté endpoint résolution
            'at' => $incident['date_resolution'] ?? null, // hypothétique colonne (sinon null silencieux)
        ];

        $enriched = [
            'schema_version' => 2,
            'id' => $incidentId,
            'status' => $incidentStatus,
            'created_at' => $incident['date_creation'] ?? null,
            'description_full' => $incident['description'] ?? null,
            'covoiturage' => [
                'id' => (int)($covoiturage['covoiturage_id'] ?? 0),
                'lieu_depart' => $covoiturage['lieu_depart'] ?? null,
                'lieu_arrivee' => $covoiturage['lieu_arrivee'] ?? null,
                'date_depart' => $covoiturage['date_depart'] ?? null,
                'heure_depart' => $covoiturage['heure_depart'] ?? null,
                'heure_arrivee' => $covoiturage['heure_arrivee'] ?? null,
            ],
            'participation' => [
                // Nouveau format
                'id' => (int)($participation['participation_id'] ?? 0),
                'places' => (int)($participation['nb_places'] ?? 0),
                'escrow' => [
                    'statut' => $escrowStatut,
                    'montant_brut' => $montantBrut,
                    'commission' => $commission,
                    'montant_chauffeur' => $montantChauffeur,
                ],
                // Champs legacy conservés pour ne pas casser le front existant
                'participation_id' => (int)($participation['participation_id'] ?? 0),
                'nb_places' => (int)($participation['nb_places'] ?? 0),
                'escrow_statut' => $escrowStatut,
                'montant_brut' => $montantBrut,
                'montant_plateforme' => $commission,
                'montant_chauffeur' => $montantChauffeur,
            ],
            'passager' => $passagerDto,
            'chauffeur' => $chauffeurDto,
            'actions' => $actions,
            'resolution' => $resolution,
            // Escrow top-level normalisé (dup des valeurs participation.escrow pour simplifier front)
            'escrow' => [
                'statut' => $escrowStatut,
                'montant_brut' => $montantBrut,
                'commission' => $commission,
                'montant_chauffeur' => $montantChauffeur,
            ],
        ];

        // Préserver les champs legacy majeurs attendus aujourd'hui dans les contrôleurs.
        $legacy = [
            'incident_id' => $incidentId,
            'description' => $incident['description'] ?? null,
            'date_creation' => $incident['date_creation'] ?? null,
            'statut' => $incidentStatus,
        ];

        // On fusionne (les nouvelles clés ne doivent pas écraser les legacy homonymes; ici noms distincts sauf description_full)
        return array_merge($legacy, $enriched);
    }
}
