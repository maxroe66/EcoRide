<?php
// backend/services/MailService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php'; // pour AppConfig

class MailService {
    private $mailer;           // objet PHPMailer
    private $config;           // tableau de config mail

    public function __construct() {
        // On récupère la configuration (valeurs simples)
        $this->config = AppConfig::mail();

        // Création de l'objet PHPMailer
        $this->mailer = new PHPMailer(true);

        // On configure le SMTP (si plus tard tu veux un mode mail() tu peux ajouter une condition)
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->config['host'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $this->config['username'];
        $this->mailer->Password = $this->config['password'];
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // simple par défaut
        $this->mailer->Port = $this->config['port'];
        $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);
    // On force l'encodage UTF-8 pour que les accents s'affichent correctement
    $this->mailer->CharSet = 'UTF-8';
    // Encodage du corps (base64 fiable pour caractères spéciaux)
    $this->mailer->Encoding = 'base64';
    }

    // Fonction pour charger un template et remplacer des mots clefs {{CLE}}
    public function renderTemplate($templateName, $vars = []) {
        $path = __DIR__ . '/../emails/templates/' . $templateName . '.html';
        if (!file_exists($path)) {
            return '<p>Template introuvable: ' . htmlspecialchars($templateName) . '</p>';
        }
        $content = file_get_contents($path);
        // On remplace chaque clé
        foreach ($vars as $key => $value) {
            $content = str_replace('{{' . $key . '}}', htmlspecialchars((string)$value), $content);
        }
        return $content;
    }

    // Envoi basique d'un mail HTML
    public function sendMail($to, $subject, $bodyHtml) {
        // Si on est en mode dry_run on ne fait pas d'envoi réelle, on log dedans un fichier
        if (!empty($this->config['dry_run'])) {
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) { @mkdir($logDir, 0777, true); }
            $logFile = $logDir . '/mail_test.log';
            $txt = "==== MAIL (dry_run) ====" . PHP_EOL . 'TO: ' . $to . PHP_EOL . 'SUBJECT: ' . $subject . PHP_EOL . $bodyHtml . PHP_EOL . PHP_EOL;
            file_put_contents($logFile, $txt, FILE_APPEND);
            return true; // on considère succès
        }
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $bodyHtml;
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('Erreur envoi mail: ' . $e->getMessage());
            return false;
        }
    }
}
