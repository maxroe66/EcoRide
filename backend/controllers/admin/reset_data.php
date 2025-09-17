<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/ResetDataService.php';

eco_require_role(['administrateur']);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { eco_json_error('Méthode non autorisée',405); }
eco_verify_csrf($_POST['csrf_token'] ?? null);
try { $pdo = Database::getMySQL(); $service = new ResetDataService($pdo); $result = $service->reset(); if (($result['success'] ?? false) !== true) { eco_json_error($result['message'] ?? 'Échec réinitialisation', 500); } eco_json_success($result); }
catch (Exception $e) { EcoLogger::log('admin', 'reset_data_error=' . $e->getMessage()); eco_json_error('Erreur lors de la réinitialisation', 500); }
