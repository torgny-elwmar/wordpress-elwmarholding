# Elwmar Holding AB

WordPress-projektet för [elwmarholding.se](https://elwmarholding.se). Repositoriet innehåller webbplatsens egenutvecklade blocktema, OpenID Connect-pluginet som används för autentisering samt verktyg för lokal utveckling och synkning från produktion.

## Teknik

- WordPress med Full Site Editing
- PHP 8.4 i den lokala miljön
- [`@wordpress/env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) för lokal WordPress och databas i Docker
- PowerShell och WP-CLI för produktionssynkning
- OpenID Connect Generic Client för SSO i produktion
- Polylang för svenska och engelska innehållsversioner

## Förutsättningar

För vanlig lokal utveckling behövs:

- Node.js och npm
- Docker Desktop med Docker-motorn startad

För att synka produktionsdata behövs dessutom:

- Windows PowerShell
- OpenSSH-klienten (`ssh.exe`)
- SSH-behörighet till värden `web01.cloud`
- Behörighet att läsa produktionsdatabasen och katalogen för uploads på servern

## Kom igång

1. Installera beroenden:

   ```powershell
   npm ci
   ```

2. Starta den lokala miljön:

   ```powershell
   npm start
   ```

   Kommandot startar automatiskt Docker Desktop om Docker-motorn inte redan körs.

3. Öppna webbplatsen:

   - Webbplats: <http://localhost:8883>
   - Administration: <http://localhost:8883/wp-admin>

Temat, OIDC-pluginet och den versionslåsta Polylang-pluginen ligger under `wp-content` och monteras genom `.wp-env.json`. Ändringar i dessa kataloger blir tillgängliga direkt i den lokala miljön.

## Tillgängliga kommandon

| Kommando | Beskrivning |
| --- | --- |
| `npm start` | Startar Docker Desktop vid behov och därefter den lokala WordPress-miljön. |
| `npm stop` | Stoppar miljön utan att radera data. |
| `npm run status` | Visar miljöns status och portar. |
| `npm run reset` | Återställer alla `wp-env`-miljöer och raderar deras lokala data. |
| `npm run destroy` | Stoppar och tar bort miljöns containrar och data. |
| `npm run plugin:check -- <plugin>` | Kontrollerar senaste tillgängliga pluginversion utan att ändra filer. |
| `npm run plugin:update -- <plugin> [version]` | Hämtar och verifierar en pluginversion från WordPress.org. |
| `npm run sync:prod` | Ersätter den lokala databasen med en kopia från produktion. |
| `npm run sync:prod:full` | Synkar databasen och ersätter lokala uploads med verifierade produktionsfiler. |

WP-CLI kan köras i miljön med exempelvis:

```powershell
npx wp-env run cli wp plugin list
```

## Uppdatera plugins

Plugins är versionshanterade och ska uppdateras lokalt, testas och distribueras via Git. Scriptet stöder `polylang` och `daggerhart-openid-connect-generic`.

Kontrollera om en uppdatering finns:

```powershell
npm run plugin:check -- polylang
```

Installera senaste stabila version från WordPress.org:

```powershell
npm run plugin:update -- polylang
```

Installera en viss tillgänglig version:

```powershell
npm run plugin:update -- polylang 3.8.7
```

Scriptet avbryter om plugin-katalogen har ocommittade ändringar. Det verifierar officiell download-URL, arkivstruktur och versionsheader, och sparar föregående version under `.dev/plugin-backups`. Testa därefter webbplatsen och pluginstatus innan ändringen committas:

```powershell
npm start
npx wp-env run cli wp plugin list
git diff -- wp-content/plugins/polylang
```

Efter verifiering committas och pushas plugin-katalogen. Kör sedan `deploy-elwmarholding` på servern. Pluginuppdateringar ska inte göras direkt i WordPress-admin i produktion.

## E-post via SMTP2GO

`wp-content/mu-plugins/smtp2go-mailer.php` konfigurerar WordPress inbyggda PHPMailer för SMTP2GO. Pluginen är generell, must-use och laddas automatiskt.

Sitens avsändaradress, avsändarnamn, SMTP-värd, port och kryptering finns i `wp-content/mu-plugins/smtp2go-mailer/config.php`. Filen får endast innehålla icke-hemliga inställningar. SMTP2GO-användarnamn och lösenord ska monteras som Docker secrets:

- `/run/secrets/smtp2go_username`
- `/run/secrets/smtp2go_password`

Generell användning och Compose-konfiguration finns i `wp-content/mu-plugins/smtp2go-mailer/README.md`. Domänen och avsändaradressen måste vara verifierade hos SMTP2GO innan ett leveranstest görs.

## Synka från produktion

> [!WARNING]
> Synkningen är destruktiv för lokal data. `sync:prod` återställer den lokala databasen. `sync:prod:full` ersätter dessutom hela den lokala katalogen `wp-content/uploads`.

Synka endast databasen:

```powershell
npm run sync:prod
```

Synka databasen och uploads:

```powershell
npm run sync:prod:full
```

Synkskriptet utför följande:

1. Startar eller verifierar den lokala `wp-env`-miljön.
2. Exporterar produktionsdatabasen över SSH och importerar den lokalt.
3. Skriver om produktionsadresser till `http://localhost:8883`.
4. Markerar miljön som `local` och blockerar sökmotorindexering.
5. Inaktiverar OIDC lokalt och tar bort dess produktionskonfiguration och loggar.
6. Skapar den lokala administratören `devadmin` med ett slumpgenererat lösenord.
7. Synkar uploads när kommandot `sync:prod:full` används och verifierar filerna med SHA-256, antal filer och total storlek.

Lösenordet för `devadmin` skrivs till `.dev/dev-admin-password.txt`. Katalogen `.dev` är Git-ignorerad och kan även innehålla tillfälliga filer under synkningen. Behandla ändå innehållet som känsligt och dela det inte.

## Projektstruktur

```text
.
├── .wp-env.json
├── package.json
├── scripts/
│   ├── sync-prod.ps1
│   └── update-plugin.ps1
└── wp-content/
   ├── mu-plugins/
   │   ├── smtp2go-mailer.php
   │   └── smtp2go-mailer/
    ├── plugins/
    │   └── daggerhart-openid-connect-generic/
    ├── themes/
    │   └── elwmarholding/
    │       ├── assets/
    │       ├── parts/
    │       ├── templates/
    │       ├── functions.php
    │       ├── style.css
    │       └── theme.json
    └── uploads/
```

### Tema

`wp-content/themes/elwmarholding` är ett blocktema med mallar för bland annat startsida, sidor, inlägg, arkiv, sökresultat och 404-sida. Globala färger, typografi och blockinställningar definieras i `theme.json`, medan kompletterande stilar finns i `style.css` och `assets/css/theme.css`.

Temat kräver WordPress 6.4 eller senare och PHP 8.0 eller senare. Den lokala miljön använder PHP 8.4.

### Autentisering

`wp-content/plugins/daggerhart-openid-connect-generic` tillhandahåller OpenID Connect-inloggning i produktion. Produktionssynkningen inaktiverar pluginet och raderar dess inställningar lokalt för att förhindra att utvecklingsmiljön använder produktionsidentiteten.

## Versionshantering och data

Följande innehåll ska inte checkas in:

- `node_modules`
- `.dev` och genererade inloggningsuppgifter
- `.wp-env.override.json`
- `wp-content/uploads`
- tillfälliga lösenordsfiler i temat

Databas och mediefiler från produktion kan innehålla personuppgifter. Använd dem endast i en skyddad lokal utvecklingsmiljö och enligt organisationens regler för datahantering.

## Felsökning

**Miljön startar inte**

Kontrollera att Docker Desktop körs och kör sedan `npm run status`. Vid behov kan miljön återskapas med `npm run destroy` följt av `npm start`. Detta raderar lokal WordPress-data.

**Produktionssynkningen kan inte ansluta**

Verifiera SSH-anslutningen separat:

```powershell
ssh web01.cloud
```

Kontrollera även att `ssh.exe`, `tar.exe`, Node.js och Docker är tillgängliga i `PATH`.

**Inloggningen fungerar inte efter synkning**

Använd användarnamnet `devadmin` och lösenordet i `.dev/dev-admin-password.txt`. Ett nytt lösenord skapas vid varje synkning.

## Licens

Elwmar Holding AB-temat distribueras under GNU General Public License v2 eller senare. Tredjepartsplugin och paketerade beroenden omfattas av sina respektive licenser.
