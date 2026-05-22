# PostToApi — Plugin LimeSurvey

Envoie automatiquement les réponses d’une enquête vers une URL externe (API, Google Apps Script, etc.) lorsqu’un participant **termine** l’enquête.

Compatible avec **LimeSurvey 7.x** (testé sur 7.0.0-RC1).

---

## Fonctionnement

1. Le participant répond à l’enquête et arrive à la page de fin (« merci »).
2. LimeSurvey enregistre la réponse et déclenche l’événement `afterSurveyComplete`.
3. Le plugin charge les réponses via l’API LimeSurvey (`getResponse`).
4. Un **POST JSON** est envoyé à l’URL configurée dans le fichier PHP (`API_URL`).

Le participant ne voit rien : l’envoi se fait en arrière-plan.

---

## Structure des fichiers

```
plugins/PostToApi/
├── PostToApi.php    # Code du plugin (URL à modifier ici)
├── config.xml       # Métadonnées pour LimeSurvey 7
└── README.md        # Ce fichier
```

---

## Installation

1. Les fichiers doivent être dans `plugins/PostToApi/`.
2. Connexion admin : `http://localhost/limesurvey/index.php/admin`
3. **Réglages** → **Gestionnaire de plugins** (`/admin/pluginmanager/sa/index`)
4. Trouver **PostToApi** → **Installer** → **Activer**

> Ce plugin **n’apparaît pas** dans l’onglet **Enquête → Plugins** : il n’a pas de réglages par enquête. Une fois activé globalement, il s’applique à toutes les enquêtes **activées** de cette instance LimeSurvey.

---

## Configuration

Ouvrir `PostToApi.php` et modifier la constante :

```php
private const API_URL = 'https://votre-serveur.com/chemin';
```

Exemple avec **Google Apps Script** :

```php
private const API_URL = 'https://script.google.com/macros/s/VOTRE_ID/exec';
```

Après chaque modification du fichier PHP, inutile de réinstaller le plugin ; un rechargement de page suffit en général.

### Prérequis

- Extension PHP **curl** activée (XAMPP : vérifier `extension=curl` dans `php.ini`)
- Enquête au statut **Activée** (sinon `responseId` peut être absent et rien n’est envoyé)

---

## Format du JSON envoyé

```json
{
  "surveyId": 851155,
  "responseId": 5,
  "answers": {
    "submitdate": "2026-05-22 21:42:47",
    "token": "tIp6a9GuucH0zga",
    "G01Q01": "AO02",
    "G02Q02": "AO03",
    "startdate": "2026-05-22 21:42:43",
    "lastpage": 2
  }
}
```

- **`surveyId`** : identifiant de l’enquête  
- **`responseId`** : identifiant de la réponse en base  
- **`answers`** : toutes les colonnes de la réponse, avec les **codes question** LimeSurvey (`G01Q01`, `G02Q02`, etc.)

---

## Exemple : Google Sheets (Apps Script)

### 1. Google Sheet — ligne 1 (en-têtes)

| Date | token | G01Q01 | G02Q02 |
|------|-------|--------|--------|

Les noms de colonnes doivent correspondre aux codes question dans LimeSurvey.

### 2. Apps Script (Extensions → Apps Script)

```javascript
function doPost(e) {
  try {
    if (!e || !e.postData || !e.postData.contents) {
      return jsonResponse({ ok: false, error: 'No POST body' });
    }

    const data = JSON.parse(e.postData.contents);
    const answers = data.answers || {};
    const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
    const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];

    const row = headers.map(function (col) {
      if (col === 'Date') return answers.submitdate || answers.datestamp || new Date();
      if (col === 'token') return answers.token || '';
      return answers[col] !== undefined && answers[col] !== null ? String(answers[col]) : '';
    });

    sheet.appendRow(row);
    return jsonResponse({ ok: true });
  } catch (err) {
    return jsonResponse({ ok: false, error: String(err) });
  }
}

function doGet() {
  return ContentService
    .createTextOutput('OK – envoyer un POST JSON (LimeSurvey)')
    .setMimeType(ContentService.MimeType.TEXT);
}

function jsonResponse(obj) {
  return ContentService
    .createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}

// Test depuis l’éditeur : exécuter testPost (pas doPost)
function testPost() {
  const fakeEvent = {
    postData: {
      contents: JSON.stringify({
        surveyId: 851155,
        responseId: 1,
        answers: {
          submitdate: '2026-05-22 21:42:47',
          token: 'test',
          G01Q01: 'AO02',
          G02Q02: 'AO03'
        }
      })
    }
  };
  Logger.log(doPost(fakeEvent).getContent());
}
```

### 3. Déploiement

1. **Déployer** → **Nouveau déploiement** → **Application web**
2. **Exécuter en tant que** : Moi
3. **Qui a accès** : **Tout le monde** (obligatoire pour que LimeSurvey puisse appeler l’URL sans compte Google)
4. Copier l’URL `/exec` dans `API_URL` du plugin

### 4. Vérifier les logs Google

**Apps Script** → **Exécutions** :

- **`doGet`** dans le navigateur : page de test (optionnel)
- **`doPost`** après une enquête ou un test : doit être **Terminée**
- Ouvrir une ligne `doPost` → voir les journaux détaillés

---

## Test de bout en bout

1. Plugin **PostToApi** activé  
2. `API_URL` renseigné  
3. Enquête **activée**  
4. Ouvrir le lien participant : `http://localhost/limesurvey/index.php/851155` (remplacer par votre ID)  
5. Répondre jusqu’à la fin  
6. Vérifier : nouvelle ligne dans le Sheet **ou** nouvelle exécution `doPost` dans Apps Script  

Test PowerShell (Windows) :

```powershell
$body = '{"surveyId":851155,"responseId":1,"answers":{"submitdate":"2026-05-22 21:42:47","token":"test","G01Q01":"AO02","G02Q02":"AO03"}}'
Invoke-WebRequest -Uri "VOTRE_URL/exec" -Method POST -Body $body -ContentType "application/json"
```

---

## Dépannage

| Problème | Cause probable | Solution |
|----------|----------------|----------|
| Rien dans le Sheet, pas de `doPost` | Plugin inactif ou enquête non activée | Activer plugin + enquête ; aller jusqu’à la page de fin |
| Pas de `doPost` dans Exécutions | Mauvaise URL ou curl désactivé | Vérifier `API_URL`, `php.ini` curl |
| `doGet` : fonction introuvable | URL ouverte dans le navigateur (GET) | Normal ; ajouter `doGet()` ou ignorer ; LimeSurvey utilise POST |
| Page connexion Google sur l’URL | Déploiement pas « Tout le monde » | Nouveau déploiement, accès public |
| `doPost` OK mais Sheet vide | Mauvais fichier ou en-têtes ligne 1 | Ouvrir le bon Sheet ; colonnes `Date`, `token`, codes question |
| `testPost` OK, enquête non | `responseId` vide | Activer l’enquête ; ne pas seulement prévisualiser |
| Erreur compatibilité plugin | `config.xml` incomplet | `author`, `license`, version `7.0` dans compatibility |

Les erreurs HTTP ne sont pas affichées au participant ; le plugin n’écrit pas encore de log LimeSurvey. Consulter **Exécutions** côté Google ou activer le debug LimeSurvey si besoin.

---

## Sécurité

- L’URL Apps Script en mode « Tout le monde » peut recevoir des POST de n’importe qui si l’URL est connue.
- Pour la production : ajouter un secret partagé (header ou champ JSON) vérifié dans Apps Script.
- Ne pas exposer de données sensibles sans HTTPS.

---

## Licence

Métadonnées du plugin : **MIT** (voir `config.xml`).

---

## Auteur / support

Plugin personnalisé pour cette installation LimeSurvey.  
Documentation LimeSurvey officielle sur les plugins : [LimeSurvey Community](https://community.limesurvey.org/documentation/).
