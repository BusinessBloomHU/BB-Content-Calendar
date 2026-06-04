# Content Calendar – WordPress Plugin

WordPress plugin social media tartalmak tervezéséhez és AI-alapú szövegíráshoz. LinkedIn, Instagram és Facebook bejegyzéseket írhatsz közvetlenül a cikk szerkesztőjéből, AI-jal generáltathatod a szövegeket, és naptárnézetben láthatod az összes tervezett publikálást.

---

## Funkciók

- **Social media metabox** – LinkedIn, Instagram és Facebook tartalom írása a post szerkesztőn belül, tabok között váltva
- **AI szövegírás** – egy gombbal generáltat platform-specifikus bejegyzést a cikk tartalma alapján
- **Emoji picker** – emoji beszúrása kurzorpozícióba, 5 kategóriában
- **Státusz- és dátumkezelés** – draft / kész / publikált státusz és tervezett dátum minden platformhoz
- **Tartalomnaptár** – FullCalendar naptárnézet az összes WordPress cikkel és social media bejegyzéssel
- **Több AI provider** – OpenAI (GPT-4o), Anthropic (Claude) és Google Gemini (ingyenes tier) támogatás

---

## Telepítés

1. Töltsd le vagy klónozd a repót
2. Másold a `content_calendar` mappát a WordPress `wp-content/plugins/` könyvtárába
3. Aktiváld a plugint a WordPress adminban (Bővítmények → Aktiválás)
4. Add meg az AI API kulcsot: **Tartalomnaptár → Beállítások**

---

## Beállítások

A plugin beállítások a **Tartalomnaptár → Beállítások** menüpont alatt érhetők el.

### AI szolgáltató

| Szolgáltató | Modellek | Megjegyzés |
|---|---|---|
| **Google Gemini** | gemini-3.5-flash, gemini-3.1-flash-lite | Ingyenes tier elérhető |
| **OpenAI** | GPT-4o, GPT-4o Mini | Fizetős, pay-as-you-go |
| **Anthropic** | Claude Sonnet 4.6, Claude Haiku 4.5 | Fizetős, pay-as-you-go |

### API kulcsok igénylése

- **Google Gemini (ingyenes):** [aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey)
- **OpenAI:** [platform.openai.com/api-keys](https://platform.openai.com/api-keys)
- **Anthropic:** [console.anthropic.com/settings/keys](https://console.anthropic.com/settings/keys)

### Post típusok

Megadható, hogy mely post típusokon jelenjen meg a social media metabox (alapértelmezett: `post`).

---

## Promptok testreszabása

A platform-specifikus AI instrukciók az `includes/class-ai-connector.php` fájl tetején, a `PLATFORM_PROMPTS` konstansban találhatók:

```php
const PLATFORM_PROMPTS = array(
    'linkedin'  => 'Írj egy professzionális LinkedIn bejegyzést...',
    'instagram' => 'Írj egy Instagram feliratot...',
    'facebook'  => 'Írj egy Facebook bejegyzést...',
);
```

---

## Fájlstruktúra

```
content_calendar/
├── content-calendar.php          # Főfájl, plugin header
├── includes/
│   ├── class-meta-box.php        # Social media metabox (mentés, megjelenítés)
│   ├── class-ai-connector.php    # AI integráció (OpenAI, Anthropic, Gemini)
│   ├── class-calendar.php        # FullCalendar naptárnézet + AJAX
│   └── class-settings.php        # Beállítások oldal
├── admin/
│   ├── views/
│   │   └── meta-box.php          # Metabox HTML sablon
│   ├── css/
│   │   └── admin.css             # Admin stílusok
│   └── js/
│       ├── admin.js              # Tab váltás, AI AJAX, emoji picker
│       └── calendar.js           # FullCalendar inicializálás
```

---

## Post meta kulcsok

A plugin az alábbi post meta kulcsokat használja (platformonként):

| Kulcs | Tartalom |
|---|---|
| `_cc_{platform}_content` | Social media szöveg |
| `_cc_{platform}_date` | Tervezett publikálási dátum (YYYY-MM-DD) |
| `_cc_{platform}_status` | Státusz: `draft` / `ready` / `published` |

A `{platform}` értéke: `linkedin`, `instagram`, `facebook`.

---

## Követelmények

- WordPress 6.0+
- PHP 7.4+
- AI API kulcs (legalább egy szolgáltatóhoz)

---

## Licensz

MIT License – szabadon használható, módosítható és terjeszthető.
