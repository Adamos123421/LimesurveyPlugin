# LimesurveyPlugin

## Description

PostToApi est un plugin que j'ai développé pour LimeSurvey permettant d’automatiser l’envoi des réponses d’un questionnaire vers Google Sheets en temps réel.
<img width="1538" height="348" alt="image" src="https://github.com/user-attachments/assets/d8cc0d9b-a57c-4b80-a33c-b58096d2f412" />


L’objectif est d’éviter le processus manuel consistant à :

- exporter les réponses depuis LimeSurvey,
- créer ou mettre à jour une feuille de calcul,
- partager les données avec l’équipe.

Grâce à ce plugin, chaque nouvelle réponse est automatiquement synchronisée avec un Google Sheet accessible par toute l’équipe.

---

# Fonctionnement

LimeSurvey étant open source, j’ai pu analyser son architecture interne afin de comprendre le processus d’enregistrement des réponses.

Lorsqu’un participant termine un questionnaire, LimeSurvey déclenche un événement PHP :

```php
afterSurveyComplete
```

Le plugin intercepte cet événement afin de récupérer automatiquement les réponses du participant.

Les données sont ensuite envoyées vers une URL externe (API, Google Apps Script, etc.).

---

# Architecture

```text
Participant
     ↓
LimeSurvey
     ↓
afterSurveyComplete (événement PHP)
     ↓
Plugin personnalisé
     ↓
Google Apps Script
     ↓
Google Sheets
```

---

# Google Apps Script
<img width="1736" height="795" alt="image" src="https://github.com/user-attachments/assets/b5e86f10-b82a-4a48-bef4-63ddc00dcea0" />


Le Google Apps Script agit comme une API intermédiaire :

- réception des données envoyées par le plugin,
- traitement des réponses,
- insertion automatique dans Google Sheets.

Cela permet :

- une synchronisation en temps réel,
- un accès partagé pour toute l’équipe,
- une centralisation immédiate des données,
- l’intégration future avec des dashboards ou outils d’analyse.

---



# Avantages

- Automatisation complète du workflow
- Synchronisation en temps réel
- Réduction des manipulations manuelles
- Collaboration simplifiée
- Architecture extensible
- Compatible avec APIs et dashboards

---

# Exemple de workflow
```text
1. Un participant complète le questionnaire.
   <img width="1748" height="691" alt="image" src="https://github.com/user-attachments/assets/1de17412-d62c-443e-907d-94b8ccbbdcd2" />

3. LimeSurvey enregistre la réponse.
  <img width="1660" height="631" alt="image" src="https://github.com/user-attachments/assets/a84646dd-51d0-40c6-8bc1-f7951e0a0b93" />

4. L’événement `afterSurveyComplete` est déclenché.
                     ↓
5. Le plugin récupère les données.
                       ↓
6. Les réponses sont envoyées au Google Apps Script.
                        ↓
8. Les données apparaissent automatiquement dans Google Sheets.
   <img width="967" height="113" alt="image" src="https://github.com/user-attachments/assets/c4787c83-24c3-475d-b46b-890f7ef5f4f1" />

 ```  

---

# Objectif du projet
Ce projet a été développé dans le but de faciliter le suivi des enquêtes et d’améliorer la collaboration entre les membres d’une équipe travaillant sur des questionnaires LimeSurvey.
Grâce à une jointure entre le Google Sheet contenant les informations des élèves et celui contenant les réponses collectées, il est possible d’obtenir une feuille de calcul mise à jour automatiquement à chaque nouvelle réponse, sans aucune infrastructure serveur supplémentaire.
Cette approche élimine entièrement l’étape d’export manuel des données depuis LimeSurvey et permet aux data scientists ainsi qu’aux équipes de suivi d’accéder à des données en temps réel pour le monitoring et l’analyse des enquêtes.
