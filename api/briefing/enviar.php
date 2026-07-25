<?php

declare(strict_types=1);

require_once __DIR__ . '/briefings.php';

briefing_cors();
briefing_options_exit();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    briefing_json(['ok' => false, 'message' => 'Método não permitido.'], 405);
}

// Honeypot
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    briefing_json(['ok' => true, 'id' => 'ok']);
}

$ip = briefing_client_ip();
if (!briefing_rate_limit_ok($ip)) {
    briefing_json(['ok' => false, 'message' => 'Muitas tentativas. Tente novamente mais tarde.'], 429);
}

try {
    briefing_ensure_dirs();

    $name = briefing_sanitize_text($_POST['name'] ?? '', 120);
    $phone = briefing_sanitize_text($_POST['phone'] ?? '', 40);
    $email = briefing_sanitize_text($_POST['email'] ?? '', 160);
    $company = briefing_sanitize_text($_POST['company'] ?? '', 160);

    $projectType = briefing_sanitize_text($_POST['project_type'] ?? '', 80);
    $goal = briefing_sanitize_text($_POST['goal'] ?? '', 200);
    $goalOther = briefing_sanitize_text($_POST['goal_other'] ?? '', 400);
    $business = briefing_sanitize_text($_POST['business'] ?? '', 2000);
    $hasWebsite = briefing_sanitize_text($_POST['has_website'] ?? '', 20);
    $currentUrl = briefing_sanitize_text($_POST['current_url'] ?? '', 300);
    $domain = briefing_sanitize_text($_POST['domain'] ?? '', 120);

    $pagesRaw = $_POST['pages'] ?? [];
    if (!is_array($pagesRaw)) {
        $pagesRaw = $pagesRaw !== '' ? [$pagesRaw] : [];
    }
    $pages = [];
    foreach ($pagesRaw as $p) {
        $sp = briefing_sanitize_text((string) $p, 80);
        if ($sp !== '') {
            $pages[] = $sp;
        }
    }
    $pages = array_values(array_unique($pages));

    $hasLogo = briefing_sanitize_text($_POST['has_logo'] ?? '', 20);
    $logoUrl = briefing_sanitize_text($_POST['logo_url'] ?? '', 500);
    $brandUrl = briefing_sanitize_text($_POST['brand_url'] ?? '', 500);
    $colorPrimary = briefing_sanitize_hex($_POST['color_primary'] ?? '');
    $colorSecondary = briefing_sanitize_hex($_POST['color_secondary'] ?? '');
    $colorAccent = briefing_sanitize_hex($_POST['color_accent'] ?? '');
    $suggestPalette = !empty($_POST['suggest_palette']);
    $fontHeading = briefing_sanitize_text($_POST['font_heading'] ?? '', 80);
    $fontBody = briefing_sanitize_text($_POST['font_body'] ?? '', 80);
    $suggestFonts = !empty($_POST['suggest_fonts']);
    $tone = briefing_sanitize_text($_POST['tone'] ?? '', 120);

    $stylesRaw = $_POST['styles'] ?? [];
    if (!is_array($stylesRaw)) {
        $stylesRaw = $stylesRaw !== '' ? [$stylesRaw] : [];
    }
    $styles = [];
    foreach ($stylesRaw as $s) {
        $ss = briefing_sanitize_text((string) $s, 80);
        if ($ss !== '') {
            $styles[] = $ss;
        }
    }
    $styles = array_values(array_unique($styles));

    $refs = [];
    foreach (['ref1', 'ref2', 'ref3'] as $rk) {
        $u = briefing_sanitize_text($_POST[$rk] ?? '', 300);
        if ($u !== '') {
            $refs[] = $u;
        }
    }
    $refsNotes = briefing_sanitize_text($_POST['refs_notes'] ?? '', 2000);
    $notes = briefing_sanitize_text($_POST['notes'] ?? '', 3000);

    if ($name === '' || $phone === '') {
        briefing_json(['ok' => false, 'message' => 'Nome e WhatsApp são obrigatórios.'], 422);
    }
    if ($projectType === '' || $goal === '') {
        briefing_json(['ok' => false, 'message' => 'Tipo de projeto e objetivo são obrigatórios.'], 422);
    }
    if (strcasecmp($goal, 'Outro') === 0) {
        if ($goalOther === '') {
            briefing_json(['ok' => false, 'message' => 'Descreva o objetivo em “Outro”.'], 422);
        }
        $goal = 'Outro: ' . $goalOther;
    }

    // Normaliza domínio (sem protocolo)
    if ($domain !== '') {
        $domain = preg_replace('#^https?://#i', '', $domain) ?? $domain;
        $domain = rtrim($domain, '/');
        $domain = strtolower($domain);
    }

    $id = briefing_new_id();
    $logoFile = briefing_store_upload('logo_file', $id, 'logo');
    $brandFile = briefing_store_upload('brand_file', $id, 'brand');

    $row = [
        'id' => $id,
        'created_at' => gmdate('c'),
        'status' => 'new',
        'name' => $name,
        'company' => $company,
        'email' => $email,
        'phone' => $phone,
        'project_type' => $projectType,
        'goal' => $goal,
        'business' => $business,
        'has_website' => $hasWebsite,
        'current_url' => $currentUrl,
        'domain' => $domain,
        'pages' => $pages,
        'refs' => $refs,
        'refs_notes' => $refsNotes,
        'has_logo' => $hasLogo,
        'logo_file' => $logoFile,
        'brand_file' => $brandFile,
        'logo_url' => $logoUrl,
        'brand_url' => $brandUrl,
        'color_primary' => $colorPrimary !== '' ? $colorPrimary : '#1052E0',
        'color_secondary' => $colorSecondary !== '' ? $colorSecondary : '#1A1D26',
        'color_accent' => $colorAccent !== '' ? $colorAccent : '#10B981',
        'suggest_palette' => $suggestPalette,
        'font_heading' => $fontHeading,
        'font_body' => $fontBody,
        'suggest_fonts' => $suggestFonts,
        'styles' => $styles,
        'tone' => $tone,
        'notes' => $notes,
        'ip' => $ip,
        'ua' => briefing_sanitize_text($_SERVER['HTTP_USER_AGENT'] ?? '', 300),
    ];

    $list = briefings_all();
    array_unshift($list, $row);
    briefings_save($list);

    briefing_json(['ok' => true, 'id' => $id], 201);
} catch (InvalidArgumentException $e) {
    briefing_json(['ok' => false, 'message' => $e->getMessage()], 422);
} catch (Throwable $e) {
    error_log('briefing enviar: ' . $e->getMessage());
    briefing_json(['ok' => false, 'message' => 'Erro no servidor.'], 500);
}
