@php($ajuste = \App\Models\Ajuste::actual())
<style>
    :root {
        --recapdam-acento: {{ $ajuste->color_acento }};
        --recapdam-texto: {{ $ajuste->color_texto }};
    }

    /* Accent: decorative lines. */
    .fi-topbar { border-bottom: 3px solid var(--recapdam-acento); }
    .fi-simple-main { border-top: 4px solid var(--recapdam-acento); }

    /* Text color: titles and highlighted text (light mode only, dark mode keeps Filament's contrast). */
    html:not(.dark) .fi-header-heading,
    html:not(.dark) .fi-section-header-heading,
    html:not(.dark) .fi-ta-header-heading,
    html:not(.dark) .fi-simple-header-heading {
        color: var(--recapdam-texto);
    }
</style>
