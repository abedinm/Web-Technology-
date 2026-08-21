<?php
// ============================================================
// icons.php - INLINE SVG ICON SET
//
// One consistent icon family, all drawn with the same 1.6 stroke
// width and rounded caps so they look like one set. SVG scales
// without blurring and takes its colour from the CSS around it,
// which a PNG or an emoji cannot do.
//
// Usage:  echo icon("calendar");
// ============================================================

function icon($name, $size = 18) {
    $paths = array(

        "grid" => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',

        "search" => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',

        "calendar" => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',

        "stethoscope" => '<path d="M6 3v6a5 5 0 0 0 10 0V3"/><path d="M4 3h3M15 3h3"/><path d="M11 14v2a5 5 0 0 0 9 3"/><circle cx="20" cy="17" r="2"/>',

        "users" => '<path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="3.5"/><path d="M22 20v-2a4 4 0 0 0-3-3.8"/><path d="M16 3.2A4 4 0 0 1 16 10.8"/>',

        "layers" => '<path d="M12 3l9 5-9 5-9-5 9-5Z"/><path d="M3 13l9 5 9-5"/>',

        "user" => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',

        "logout" => '<path d="M15 17l5-5-5-5"/><path d="M20 12H9"/><path d="M12 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h6"/>',

        "check" => '<path d="M20 6L9 17l-5-5"/>',

        "x" => '<path d="M18 6L6 18M6 6l12 12"/>',

        "clock" => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',

        "plus" => '<path d="M12 5v14M5 12h14"/>',

        "edit" => '<path d="M4 20h4l11-11a2.5 2.5 0 0 0-3.5-3.5L4 16v4Z"/><path d="M14 6l4 4"/>',

        "trash" => '<path d="M4 7h16"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M6 7l1 13h10l1-13"/>',

        "alert" => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/>',

        "database" => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/><path d="M4 12v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',

        "arrow-left" => '<path d="M19 12H5"/><path d="M11 18l-6-6 6-6"/>',

        "arrow-right" => '<path d="M5 12h14"/><path d="M13 6l6 6-6 6"/>',

        "phone" => '<path d="M5 3h4l2 5-2.5 1.5a12 12 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2.2 2A17 17 0 0 1 3 5.2 2 2 0 0 1 5 3Z"/>',

        "mail" => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
    );

    if (!isset($paths[$name])) {
        return "";
    }

    return '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" '
         . 'stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" '
         . 'aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
}

// The DoctorConnect logo mark: a pulse line inside a rounded square.
function logo_mark($size = 30) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 32 32" fill="none" aria-hidden="true">'
         . '<rect width="32" height="32" rx="9" fill="currentColor"/>'
         . '<path d="M6 17.5h4.2l2.1-5.4 3.4 9.2 2.4-6 1.6 2.2H26" stroke="#ffffff" stroke-width="2.1" '
         . 'stroke-linecap="round" stroke-linejoin="round"/></svg>';
}
