<?php
// Service pour la logique métier liée à la terminaison d'un covoiturage
// Ce service utilise le modèle CovoiturageTerminer


require_once __DIR__ . '/../models/CovoiturageTerminer.php';
require_once __DIR__ . '/../models/Participant.php';
require_once __DIR__ . '/MailService.php';

class CovoiturageTerminerService {
    private $model;

    /**
     * Constructeur : injection du modèle
     */
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->model = new CovoiturageTerminer($pdo);
    }

    /**
     * Termine un covoiturage si l'utilisateur est bien le conducteur et que le covoiturage est en cours
     */
    public function terminer($user_id, $covoiturage_id) {
        $cov = $this->model->getCovoiturage($covoiturage_id);
        if (!$cov || $cov['conducteur_id'] != $user_id) {
            return ['success' => false, 'message' => 'Covoiturage non trouvé ou non autorisé'];
        }
        if ($cov['statut'] !== 'en_cours') {
            return ['success' => false, 'message' => 'Ce covoiturage n\'est pas en cours'];
        }
        $this->model->terminerCovoiturage($covoiturage_id);
        $this->model->marquerParticipationsEnAttente($covoiturage_id);

        // Envoi des mails aux participants et au chauffeur
        $participantModel = new Participant($this->pdo);
        // ========= ENVOI EMAILS (avec templates) =========
        $mailService = new MailService();
        $emailsParticipants = $participantModel->getEmailsByCovoiturage($covoiturage_id);
        $emailChauffeur = $participantModel->getChauffeurEmail($covoiturage_id);
        $subject = 'EcoRide - Fin de covoiturage';
        // On passe quelques variables simples
        $vars = [
            'COVOIT_ID' => $covoiturage_id,
            // URL d'espace : ici on met un placeholder (a adapter si front a une autre route)
            'URL_ESPACE' => 'http://localhost/front_end/html/espace.html'
        ];
        // Corps pour participant
        $bodyParticipant = $mailService->renderTemplate('fin_covoiturage_participant', $vars);
        foreach ($emailsParticipants as $email) {
            $mailService->sendMail($email, $subject, $bodyParticipant);
        }
        // Corps pour chauffeur
        if ($emailChauffeur) {
            $bodyChauffeur = $mailService->renderTemplate('fin_covoiturage_chauffeur', $vars);
            $mailService->sendMail($emailChauffeur, $subject, $bodyChauffeur);
        }

        return [
            'success' => true,
            'message' => 'Covoiturage terminé ! Les participants et le chauffeur ont été notifiés par mail.'
        ];
    }
}
