<?php
/**
 * Helper de réponse JSON unifié.
 * Objectifs:
 *  - Ne pas casser le front existant (clés métier racine conservées au début)
 *  - Introduire progressivement une enveloppe standard { success, data, meta, message }
 *  - Permettre migration douce: nouvelle consommation via data.*, anciens écrans intactes
 */

if (!function_exists('eco_json_raw')) {
    function eco_json_raw(array $payload, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('eco_json_success')) {
    /**
     * Ancien helper conservé (wrap vers eco_json_response). On garde la signature pour compat.
     */
    function eco_json_success(array $payload, int $code = 200): void {
        eco_json_response($payload, true, null, $code);
    }
}

if (!function_exists('eco_json_error')) {
    /**
     * Ancien helper conservé (wrap vers eco_json_response erreur)
     */
    function eco_json_error(string $message, int $code = 400, array $data = []): void {
        eco_json_response($data, false, $message, $code);
    }
}

if (!function_exists('eco_json_response')) {
    /**
     * Nouveau helper unifié.
     * - $originalPayload : tableau potentiellement déjà structuré
     * - $success : bool
     * - $message : string|null
     * Politique de compatibilité:
     *  1. Si $originalPayload contient déjà 'success' => on suppose format existant, on renvoie tel quel (pas d'altération agressive)
     *  2. Sinon:
     *     - On duplique les clés métier dans data
     *     - On laisse aussi les clés métier à plat (phase transitoire) pour ne rien casser
     *  3. meta: si fournie dans payload ou détectée, elle est exposée aux deux niveaux (racine.meta et data.meta?) -> on choisit racine uniquement.
     */
    function eco_json_response(array $originalPayload, bool $success = true, ?string $message = null, int $code = 200): void {
        // Détection format déjà normalisé
        if (array_key_exists('success', $originalPayload)) {
            // On s'assure seulement qu'un champ data existe pour la nouvelle convention sans supprimer les anciennes clés
            if (!array_key_exists('data', $originalPayload)) {
                $clone = $originalPayload;
                unset($clone['success'], $clone['message'], $clone['meta']);
                $originalPayload['data'] = $clone ?: new stdClass();
            }
            eco_json_raw($originalPayload, $code);
        }

        // Format legacy: on construit enveloppe progressive
        $meta = null;
        // Si meta déjà fournie dans originalPayload on la retire pour la mettre au bon endroit
        if (isset($originalPayload['meta']) && is_array($originalPayload['meta'])) {
            $meta = $originalPayload['meta'];
            unset($originalPayload['meta']);
        }

        // data: duplication (transition). Dans une phase ultérieure, on pourra retirer la copie racine.
        $data = $originalPayload ?: [];

        $response = [
            'success' => $success,
            'data' => $data ?: new stdClass(),
            'message' => $message,
        ];
        if ($meta) { $response['meta'] = $meta; }

        // Phase transitoire: réinjecter les clés métier au niveau racine (sauf si collision noms réservés)
        foreach ($data as $k => $v) {
            if (!in_array($k, ['success','data','message','meta'], true)) {
                $response[$k] = $v; // rétro-compat
            }
        }

        eco_json_raw($response, $code);
    }
}

/**
 * Futur: possibilité d'un mode strict (sans duplication) activé via constante.
 * if (defined('ECORIDE_RESPONSE_STRICT') && ECORIDE_RESPONSE_STRICT) { ... }
 */
?>