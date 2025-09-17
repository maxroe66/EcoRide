<?php
// services/AnnulationService.php
// Service pour la logique métier liée à l'annulation de covoiturage ou de participation
// Ce fichier centralise les vérifications, la validation et l'appel au modèle Annulation

require_once __DIR__ . '/../models/Annulation.php';
require_once __DIR__ . '/../models/Participant.php'; // pour récupérer emails participants
require_once __DIR__ . '/MailService.php';

class AnnulationService
{
    private $pdo;
    private $annulationModel;

    // Constructeur : on passe la connexion PDO
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->annulationModel = new Annulation($pdo);
    }

    // Fonction principale pour annuler un covoiturage ou une participation
    public function annuler($user_id, $data)
    {
    // TODO: Extension future possible : en cas d'annulation d'une participation avant le départ,
    // déclencher un refund automatique de l'escrow si un hold existe.
        $type = isset($data['type']) ? $data['type'] : '';
        $id = isset($data['id']) ? intval($data['id']) : 0;

        // Vérifier que les paramètres sont bien fournis
        if (!$type || !$id) {
            return ['success' => false, 'message' => 'Paramètres manquants'];
        }

        if ($type === 'covoiturage') {
            // a. Annuler un covoiturage proposé par l'utilisateur
            $covoiturage = $this->annulationModel->getCovoiturageById($id);
            if (!$covoiturage || $covoiturage['conducteur_id'] != $user_id) {
                return ['success' => false, 'message' => 'Covoiturage non trouvé ou non autorisé'];
            }
            if (in_array($covoiturage['statut'], ['termine','en_cours'], true)) {
                return ['success' => false, 'message' => 'Ce covoiturage ne peut plus être annulé'];
            }
            if ($covoiturage['statut'] === 'annule') {
                return ['success' => false, 'message' => 'Ce covoiturage est déjà annulé'];
            }
            $this->annulationModel->annulerCovoiturage($id);
            $message = 'Covoiturage annulé avec succès.';
            // ===== Envoi email aux participants (simple) =====
            // On ne veut pas limiter à 'en_attente_validation' ici car avant le départ les statuts sont souvent 'confirmee'.
            $sqlEmails = "SELECT u.email FROM participation p JOIN utilisateur u ON p.utilisateur_id = u.utilisateur_id
                          WHERE p.covoiturage_id = ? AND p.statut IN ('confirmee','en_attente_validation')";
            $stmtEmails = $this->pdo->prepare($sqlEmails);
            $stmtEmails->execute([$id]);
            $emailsParticipants = $stmtEmails->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($emailsParticipants)) {
                $mailService = new MailService();
                // Préparation données pour template
                $vars = [
                    'COVOIT_ID' => $id,
                    // Colonnes détaillées non fiables dans ce schéma actuel → on laisse vide / neutre
                    'DEPART' => '',
                    'ARRIVEE' => '',
                    'DATE_HEURE' => '',
                    'URL_RECHERCHE' => 'http://localhost/front_end/html/recherche.html'
                ];
                $body = $mailService->renderTemplate('annulation_covoiturage', $vars);
                $subject = 'EcoRide - Covoiturage annulé';
                foreach ($emailsParticipants as $email) {
                    $mailService->sendMail($email, $subject, $body);
                }
            }
        } elseif ($type === 'participation') {
            // b. Annuler une participation de l'utilisateur
            $participation = $this->annulationModel->getParticipationById($id, $user_id);
            if (!$participation) {
                return ['success' => false, 'message' => 'Participation non trouvée ou non autorisée'];
            }
            if (in_array($participation['statut'], ['validee','probleme'], true)) {
                return ['success' => false, 'message' => 'Cette participation ne peut plus être annulée'];
            }
            if ($participation['statut'] === 'annulee') {
                return ['success' => false, 'message' => 'Cette participation est déjà annulée'];
            }
            $this->annulationModel->annulerParticipation($id);
            $message = 'Participation annulée avec succès.';
        } else {
            return ['success' => false, 'message' => "Type d'annulation invalide"];
        }

        // Réponse de succès
        return [
            'success' => true,
            'message' => $message
        ];
    }
}
