# Guide admin ISC Kindu

Ce document explique comment utiliser l'espace admin pour publier les donnees qui alimentent le frontend ISC Kindu.

## Acces admin

- URL admin : `https://isc-kindu-backend.onrender.com/admin/login`
- Depuis le frontend, le petit bouton `AMG` mene vers cette page.
- Seuls les utilisateurs avec droit administrateur peuvent entrer.

## Regle importante de publication

Une donnee n'apparait sur le site public que si :

1. Elle est enregistree dans le bon module admin.
2. Son type correspond a la page frontend.
3. La case de publication est active :
   - `Publier sur le site` pour Actualites, Publications, Medias.
   - `Visible sur le site` pour Blocs.
   - `Afficher sur le site` pour Enseignants.
   - `Statut = Publie` pour Palmares.
4. Le frontend a fini de charger les donnees depuis l'API.

Apres une modification, attendre parfois 30 secondes a 2 minutes selon le cache/deploiement.

## Parametres institution : contacts du site

Menu admin : `Pilotage > Parametres institution`

Champs importants :

| Cle admin | Utilisation frontend |
| --- | --- |
| `institution.email` | E-mail public du site et liens `mailto:` |
| `institution.phone` | Telephone public du site et liens `tel:` |
| `institution.address` | Adresse affichee dans les blocs contact/footer |
| `institution.name` | Nom complet de l'institution |
| `institution.short_name` | Nom court |
| `institution.logo_url` | Logo institutionnel |
| `admissions.is_open` | Ouverture/fermeture des inscriptions |
| `admissions.academic_year` | Annee academique affichee/utilisee |

Pour changer le contact public :

1. Aller dans `Parametres institution`.
2. Modifier `institution.email`, `institution.phone`, `institution.address`.
3. Cliquer `Enregistrer`.
4. Le frontend lit ces valeurs via `/api/site/settings`.

Important : le menu `Messages` sert a lire les messages envoyes par les visiteurs. Ce n'est pas la ou l'on change le contact public.

## Actualites / Blog

Menu admin : `Publications du site > Actualites / blog`

Alimente :

- Page frontend `blog.html`
- API `/api/news`
- Recherche du site

Champs utiles :

- `Titre` : titre visible.
- `Categorie` : exemple `Actualites`, `Communique`, `Evenement`.
- `Resume` : court texte affiche dans les cartes.
- `Contenu` : texte complet.
- `Image` ou `URL image` : image de couverture.
- `Date de publication` : date publique.
- `Publier sur le site` : obligatoire pour affichage frontend.

Si la case `Publier sur le site` n'est pas cochee, l'actualite reste en admin mais ne sort pas sur le frontend.

## Documents, bulletins et fichiers scolaires

Menu admin : `Publications du site > Documents`

Alimente :

- Page frontend `documents.html`
- Liens de telechargement dans le site
- API `/api/documents`

Types a utiliser :

| Besoin | Type de publication |
| --- | --- |
| Document general | `Document` |
| Bulletin inscription Licence | `Bulletin Licence` |
| Bulletin inscription Master | `Bulletin Master` |
| Echeancier ou fichier lie aux frais | `Echeancier` |
| Ressource bibliotheque | `Ressource` ou `Bibliotheque` |

Pour que les boutons de telechargement fonctionnent :

1. Creer une publication.
2. Choisir le bon type.
3. Ajouter le fichier dans `Fichier` ou `URL fichier`.
4. Cocher `Publier sur le site`.
5. Enregistrer.

Slugs recommandes :

| Besoin | Slug recommande |
| --- | --- |
| Bulletin inscription Licence | `bulletin-licence` |
| Bulletin inscription Master | `bulletin-master` |
| Echeancier des frais | `echeancier` |

Les liens frontend `data-backend-route="/documents/bulletin-licence"`, `bulletin-master`, `echeancier` cherchent d'abord le slug exact, puis le dernier document publie du type correspondant. Ils restent en attente si aucun fichier publie ne correspond.

Si le lien du PDF renvoie 404 apres un redeploiement, verifier `Pilotage > Production > Stockage`. Sur Render, les fichiers envoyes depuis l'admin doivent etre conserves par un disque persistant ou un stockage externe. Apres correction du stockage, renvoyer le PDF dans la publication concernee.

## Nos frais

Menu admin : `Publications du site > Nos frais`

Alimente :

- Page frontend `nos-frais.html`
- API `/api/fees` et `/api/frais`

Procedure :

1. Creer une publication.
2. Type : `Frais`.
3. Mettre un titre clair, par exemple `Frais academiques 2026-2027`.
4. Ajouter le fichier PDF ou l'URL du fichier.
5. Cocher `Publier sur le site`.
6. Enregistrer.

S'il n'y a aucun frais publie, le frontend affiche le message vide : fichier a ajouter depuis le backend.

## Palmares et diplomes

Menu admin : `Publications du site > Palmares et diplomes`

Alimente :

- Page frontend `nos-palmares.html`
- API `/api/palmares`

Procedure :

1. Creer une liste.
2. Remplir `Titre`, `Cycle`, `Annee academique`, `Section`, `Option/Filiere`, `Promotion`.
3. Dans `Etudiants diplomes`, entrer une ligne par etudiant :

```text
Matricule;Nom;Postnom;Prenom;Sexe;Pourcentage;Mention
ISC-2026-0001;KASONGO;MUTOMBO;Jean;M;75;Distinction
```

4. Mettre `Statut = Publie`.
5. Enregistrer.

Si le statut reste `Brouillon`, la liste ne s'affiche pas sur le frontend.

## Enseignants

Menu admin : `Structure du site > Enseignants`

Alimente :

- Page frontend `nos-enseignants.html`
- API `/api/teachers`

Pour afficher un enseignant :

1. Creer un membre.
2. Mettre `Role = enseignant`.
3. Completer `Nom complet`, `Titre`, `Departement / service`, `Biographie`, photo si disponible.
4. Cocher `Afficher sur le site`.
5. Enregistrer.

Si le role n'est pas `enseignant`, le membre peut exister en admin mais ne pas apparaitre dans la page `Nos enseignants`.

## Medias et galerie

Menu admin : `Structure du site > Medias et galerie`

Alimente :

- Page frontend `media-center.html`
- API `/api/gallery`

Procedure :

1. Creer un media.
2. `Collection` : utiliser `gallery` pour la galerie publique.
3. Ajouter le fichier image/video ou une URL existante.
4. Ajouter une legende.
5. Cocher `Publier sur le site`.
6. Enregistrer.

Sans publication active, la galerie reste vide.

## Blocs institution

Menu admin : `Structure du site > Institution et blocs`

Alimente :

- Bloc vide du module Institution sur `services.html`
- API `/api/institution/blocks`

Pour remplir le bloc vide du module Institution :

1. Creer un bloc.
2. Choisir `Groupe = Institution - presentation` (`institution_block`).
3. Remplir `Titre`, `Court texte`, `Contenu`.
4. Ajouter un lien si necessaire.
5. Cocher `Visible sur le site`.
6. Enregistrer.

Attention :

- `institution_block` sert au contenu editorial visible dans le bloc institution.
- `institution_service` sert surtout aux liens/services institutionnels.
- `home_service` sert aux anciens emplacements accueil. Ne pas l'utiliser pour remplir le bloc institution actuel.

## Opportunites et offres

Menu admin : `Publications du site > Opportunites et offres`

Alimente :

- Page frontend `travailler-a-isc/opportunites.html`
- Page frontend `travailler-a-isc/offres.html`
- API `/api/opportunites` et `/api/emplois`

Types utiles :

| Besoin | Type |
| --- | --- |
| Opportunite generale | `Opportunite` |
| Offre publique | `Offre` |
| Emploi | `Emploi` |
| Stage | `Stage` |

Les formulaires publics d'opportunites/offres creent des propositions non publiees. L'admin doit les verifier, les corriger si besoin, puis cocher `Publier sur le site`.

## Recherche et societe

Menu admin : `Publications du site > Recherche et societe`

Types disponibles :

- `Article`
- `Article scientifique`
- `These`
- `Formation enseignant`
- `Conference`
- `Seminaire`
- `Centre de recherche`
- `Projet`
- `Recherche`
- `Hackathon`
- `Realisation etudiant`
- `Travail Licence`
- `Travail Master`
- `Travail etudiant`
- `Travail enseignant`
- `Stage academique`

Ces donnees sont disponibles via `/api/recherche-societe` et dans la recherche du site.

## Pages du site

Menu admin : `Structure du site > Pages du site`

Ce module gere des pages statiques ou textes longs publies par API :

- Titre
- Slug
- Resume
- Contenu
- Image
- Publier sur le site

Utiliser ce module pour des contenus institutionnels longs qui ne sont pas une actualite ou une publication fichier.

## Navigation du site

Menu admin : `Pilotage > Navigation du site`

Ce module expose des liens via `/api/site/menus`.

Champs :

- `Libelle`
- `URL`
- `Menu` : `main`, `topbar`, `footer`, `student`, `institution`, `formation`
- `Parent`
- `Ordre`
- `Afficher dans l API`

Note : le frontend statique actuel garde encore une partie de sa navigation dans les fichiers HTML. Les menus API sont utiles pour extensions ou futures synchronisations.

## Messages, inscriptions et suivi

Menu admin : `Inscriptions et contact`

Modules :

- `Demandes inscription` : dossiers envoyes par les candidats/etudiants.
- `Registre inscriptions` : registre interne des inscriptions.
- `Messages` : messages envoyes depuis le formulaire contact.
- `Commentaires etudiants` : retours venant de l'espace etudiant.

Ces modules servent au suivi administratif. Ils ne changent pas directement les textes publics du site.

## Comment verifier si une publication sort sur le frontend

1. Verifier la case de publication ou le statut.
2. Verifier le type choisi.
3. Verifier qu'un titre est rempli.
4. Pour fichiers, verifier `Fichier` ou `URL fichier`.
5. Pour images, verifier `Image` ou `URL image`.
6. Ouvrir l'endpoint API correspondant :

```text
https://isc-kindu-backend.onrender.com/api/news
https://isc-kindu-backend.onrender.com/api/documents
https://isc-kindu-backend.onrender.com/api/fees
https://isc-kindu-backend.onrender.com/api/palmares
https://isc-kindu-backend.onrender.com/api/gallery
https://isc-kindu-backend.onrender.com/api/teachers
https://isc-kindu-backend.onrender.com/api/opportunites
https://isc-kindu-backend.onrender.com/api/site/settings
```

Si la donnee apparait dans l'API mais pas encore sur le site, vider le cache du navigateur ou attendre le rafraichissement Cloudflare.

## Bonnes pratiques

- Ne pas publier de donnees test.
- Toujours choisir le bon type avant d'enregistrer.
- Laisser le slug vide si vous voulez qu'il soit genere automatiquement.
- Utiliser des titres courts et clairs.
- Ajouter des images compressees si possible.
- Pour les fichiers publics, preferer PDF.
- Apres publication, verifier la page frontend sur ordinateur et telephone.
