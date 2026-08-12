<?php

namespace Database\Seeders;

use App\Models\AdmissionApplication;
use App\Models\AcademicYear;
use App\Models\ContentBlock;
use App\Models\DocumentType;
use App\Models\Event;
use App\Models\Level;
use App\Models\MenuItem;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Promotion;
use App\Models\Publication;
use App\Models\Section;
use App\Models\SiteSetting;
use App\Models\StudentComment;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedSettings();
        $this->seedAcademicCore();
        $this->seedMenu();
        $this->seedPages();
        $this->seedSectionsAndPrograms();
        $this->seedBlocks();
        $this->seedContent();
        $this->seedUsersAndStudentWallet();
    }

    private function seedAcademicCore(): void
    {
        $year = AcademicYear::updateOrCreate(
            ['code' => $this->academicYear()],
            [
                'name' => $this->academicYear(),
                'starts_on' => now()->startOfYear()->toDateString(),
                'ends_on' => now()->endOfYear()->toDateString(),
                'status' => 'active',
                'is_current' => true,
            ]
        );

        AcademicYear::where('id', '!=', $year->id)->update(['is_current' => false]);

        $levels = [
            ['L1', 'Licence 1', 'Licence', 1],
            ['L2', 'Licence 2', 'Licence', 2],
            ['L3', 'Licence 3', 'Licence', 3],
            ['M1', 'Master 1', 'Master', 4],
            ['M2', 'Master 2', 'Master', 5],
        ];

        foreach ($levels as [$code, $name, $cycle, $order]) {
            $level = Level::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'cycle' => $cycle,
                    'sort_order' => $order,
                    'is_active' => true,
                ]
            );

            Promotion::updateOrCreate(
                ['code' => $code],
                [
                    'level_id' => $level->id,
                    'name' => $name,
                    'sort_order' => $order,
                    'is_active' => true,
                ]
            );
        }

        foreach ([
            ['Diplome ou attestation', 'diplome-ou-attestation', true, 1],
            ['Photo passeport', 'photo-passeport', true, 2],
            ['Piece d identite', 'piece-identite', true, 3],
            ['Autre document', 'autre-document', false, 99],
        ] as [$name, $slug, $required, $order]) {
            DocumentType::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => 'Document exige pour le controle du dossier.',
                    'is_required' => $required,
                    'is_active' => true,
                    'sort_order' => $order,
                ]
            );
        }
    }

    private function seedSettings(): void
    {
        $settings = [
            'institution.name' => ['ISC KINDU'],
            'institution.short_name' => ['ISC KINDU'],
            'institution.code' => ['ISC_KINDU'],
            'institution.logo_url' => ['/images/site/logo.jpg'],
            'institution.email' => ['contact@isc-kindu.test'],
            'institution.phone' => ['+243 000 000 000'],
            'institution.address' => ['Kindu, Maniema, RDC'],
            'admissions.is_open' => [true, 'boolean', 'admissions'],
            'admissions.academic_year' => [$this->academicYear(), 'text', 'admissions'],
        ];

        foreach ($settings as $key => $definition) {
            SiteSetting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $definition[0],
                    'type' => $definition[1] ?? 'text',
                    'group' => $definition[2] ?? 'general',
                ]
            );
        }
    }

    private function seedMenu(): void
    {
        $parents = [
            ['Accueil', '/index.html', 1],
            ['Institution', '/aboutus.html', 2],
            ['Formation', '/formation/licence.html', 3],
            ['Vie etudiante', '/page/new-isigien.html', 4],
            ['Recherche et societe', '/services-a-la-societe.html', 5],
            ['Contact', '/contact.html', 6],
        ];

        $parentModels = [];

        foreach ($parents as [$label, $url, $order]) {
            $parentModels[$label] = MenuItem::updateOrCreate(
                ['location' => 'main', 'label' => $label],
                [
                    'parent_id' => null,
                    'url' => $url,
                    'sort_order' => $order,
                    'is_active' => true,
                ]
            );
        }

        $children = [
            'Institution' => [
                ['Decouvrir l ISC-KINDU', '/aboutus.html#apropos', 1],
                ['Nos services', '/services.html', 2],
                ['Bourse ISC Kindu', '/bourse-isc-kindu.html', 3],
                ['Galerie', '/media-center.html', 4],
                ['Blog', '/blog.html', 5],
            ],
            'Formation' => [
                ['Licence LMD', '/formation/licence.html', 1],
                ['Master LMD', '/formation/master.html', 2],
                ['Centre de Formation', '/formation/centre-de-formation.html', 3],
                ['Cisco Academie', '/formation/cisco-academie.html', 4],
                ['Nos enseignants', '/nos-enseignants.html', 5],
                ['Inscription', '/inscription.html', 6],
                ['Scolarite et archives', '/documents.html', 7],
                ['Nos diplomes', '/nos-diplomes.html', 8],
                ['Nos palmares', '/nos-palmares.html', 9],
            ],
            'Vie etudiante' => [
                ['Bienvenue aux nouveaux', '/page/new-isigien.html', 1],
                ['College des etudiants', '/page/college.html', 2],
                ['Nos alumni', '/page/alumni.html', 3],
                ['Opportunites', '/travailler-a-isc/opportunites.html', 4],
                ['Connexion etudiant', '/login.html', 5],
            ],
            'Recherche et societe' => [
                ['Travaux de Licence', '/recherche-societe/travaux-licence.html', 1],
                ['Travaux de Master', '/recherche-societe/travaux-master.html', 2],
                ['Publications enseignants', '/recherche-societe/publications-enseignants.html', 3],
                ['Nos projets', '/recherche-societe/nos-projets.html', 4],
                ['Hackathon', '/recherche-societe/hackathon.html', 5],
                ['Realisations', '/recherche-societe/realisations.html', 6],
            ],
        ];

        foreach ($children as $parentLabel => $items) {
            foreach ($items as [$label, $url, $order]) {
                MenuItem::updateOrCreate(
                    ['location' => 'main', 'label' => $label],
                    [
                        'parent_id' => $parentModels[$parentLabel]?->id,
                        'url' => $url,
                        'sort_order' => $order,
                        'is_active' => true,
                    ]
                );
            }
        }

        foreach ([
            ['topbar', 'S inscrire a l ISC Kindu', '/inscription.html', 1],
            ['topbar', 'Connexion etudiant', '/login.html', 2],
            ['topbar', 'Nos frais', '/nos-frais.html', 3],
            ['footer', 'Nos documents', '/documents.html', 1],
            ['footer', 'Contactez-nous', '/contact.html', 2],
        ] as [$location, $label, $url, $order]) {
            MenuItem::updateOrCreate(
                ['location' => $location, 'label' => $label],
                [
                    'parent_id' => null,
                    'url' => $url,
                    'sort_order' => $order,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedPages(): void
    {
        $pages = [
            ['Accueil', 'accueil', 'Page d accueil institutionnelle ISC KINDU.', '/images/site/photo-1.jpg'],
            ['Institution', 'institution', 'Presentation de l ISC KINDU et de son organisation.', '/images/site/photo-2.jpg'],
            ['Nos services', 'services', 'Blocs institutionnels a publier depuis le backend.', '/images/site/photo-3.jpg'],
            ['Bourse ISC Kindu', 'bourse-isc-kindu', 'Informations de bourse a completer depuis le backend.', '/images/site/photo-4.jpg'],
            ['Formation', 'formation', 'Vue generale des formations organisees.', '/images/site/photo-5.jpg'],
            ['Licence LMD', 'licence', 'Programmes de licence organises a l ISC KINDU.', '/images/site/photo-6.jpg'],
            ['Master LMD', 'master', 'Programmes de master a publier si disponibles.', '/images/site/photo-7.jpg'],
            ['Inscription', 'inscription', 'Parcours candidat et inscription en ligne.', '/images/site/photo-8.jpg'],
            ['Documents', 'documents', 'Documents et ressources publies depuis l administration.', '/images/site/photo-9.jpg'],
            ['Nos diplomes', 'diplomes', 'Diplomes et documents academiques publics.', '/images/site/photo-4.jpg'],
            ['Nos palmares', 'palmares', 'Palmares et listes de diplomes publies depuis le backend.', '/images/site/photo-5.jpg'],
            ['Nos frais', 'frais', 'Fichiers des frais a publier depuis le backend.', '/images/site/photo-6.jpg'],
            ['Medias', 'medias', 'Galerie photo, video et media-center.', '/images/site/photo-7.jpg'],
            ['Blog', 'blog', 'Actualites et annonces publiees depuis le backend.', '/images/site/photo-8.jpg'],
            ['Nos enseignants', 'enseignants', 'Liste des enseignants et membres actifs.', '/images/site/photo-9.jpg'],
            ['Vie etudiante', 'vie-etudiante', 'Pages utiles aux nouveaux etudiants.', '/images/site/photo-1.jpg'],
            ['College des etudiants', 'college', 'Informations du college des etudiants.', '/images/site/photo-2.jpg'],
            ['Alumni', 'alumni', 'Espace communautaire des anciens etudiants.', '/images/site/photo-3.jpg'],
            ['Opportunites', 'opportunites', 'Offres, stages et opportunites publies depuis le backend.', '/images/site/photo-4.jpg'],
            ['Recherche et societe', 'recherche-societe', 'Travaux, projets, realisations et publications.', '/images/site/photo-5.jpg'],
            ['Travaux de licence', 'travaux-licence', 'Travaux de licence a publier depuis le backend.', '/images/site/photo-6.jpg'],
            ['Travaux de master', 'travaux-master', 'Travaux de master a publier depuis le backend.', '/images/site/photo-7.jpg'],
            ['Publications enseignants', 'publications-enseignants', 'Articles scientifiques et publications.', '/images/site/photo-8.jpg'],
            ['Contact', 'contact', 'Coordonnees et formulaire de contact.', '/images/site/photo-9.jpg'],
            ['Politique de confidentialite', 'politique-de-confidentialite', 'Texte legal a completer.', '/images/site/photo-1.jpg'],
            ['Cookies', 'cookies', 'Politique cookies a completer.', '/images/site/photo-2.jpg'],
        ];

        foreach ($pages as [$title, $slug, $excerpt, $imageUrl]) {
            Page::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'body' => 'Contenu a completer depuis l espace administrateur.',
                    'image_url' => $imageUrl,
                    'is_published' => true,
                ]
            );
        }
    }

    private function seedSectionsAndPrograms(): void
    {
        $sections = [
            'Gestion commerciale et administrative' => [
                'Comptabilite et finances',
                'Marketing',
                'Fiscalite',
                'Douanes et accises',
                'Banque et assurance',
                'Entrepreneuriat',
                'Sciences et techniques du secretariat de direction',
            ],
            'Gestion informatique' => [
                'Informatique de gestion',
                'Reseaux et techniques de maintenance',
            ],
        ];

        $order = 1;

        foreach ($sections as $sectionName => $programs) {
            $section = Section::firstOrCreate(
                ['slug' => Str::slug($sectionName)],
                [
                    'name' => $sectionName,
                    'description' => 'Description a completer par les informations officielles.',
                    'sort_order' => $order++,
                    'is_active' => true,
                ]
            );

            foreach ($programs as $programName) {
                Program::firstOrCreate(
                    ['slug' => Str::slug($programName)],
                    [
                        'section_id' => $section->id,
                        'name' => $programName,
                        'cycle' => 'Licence',
                        'description' => 'Programme a completer par les informations officielles.',
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function seedBlocks(): void
    {
        $blocks = [
            ['home_slide', 'home_slide.welcome', 'Bienvenue a l ISC KINDU', 'Message d accueil a completer depuis le backend.', 'Publiez ici le message principal de la page d accueil.', '/images/site/photo-2.jpg', '/actualites.html', 'Lire les actualites', 'fas fa-graduation-cap', 1],
            ['home_slide', 'home_slide.admissions', 'Inscriptions en ligne', 'Compte, dossier et portefeuille etudiant.', 'Texte a completer pour presenter les inscriptions.', '/images/site/photo-1.jpg', '/inscription.html', 'Commencer', 'fas fa-file-signature', 2],
            ['home_card', 'home_card.inscriptions', 'Inscriptions', 'Informations utiles pour demarrer une inscription.', 'Ce bloc peut etre modifie dans l admin.', '/images/site/photo-2.jpg', '/inscription.html', 'S inscrire', 'fas fa-user-plus', 1],
            ['home_card', 'home_card.sections', 'Sections et filieres', 'Apercu des sections organisees a l ISC KINDU.', 'Ce bloc mene vers les sections gerees depuis le backend.', '/images/site/photo-3.jpg', '/formation/licence.html', 'Voir les sections', 'fas fa-layer-group', 2],
            ['home_card', 'home_card.publications', 'Publications', 'Communiques et documents officiels.', 'Ce bloc mene vers les publications admin.', '/images/site/photo-1.jpg', '/documents.html', 'Voir les publications', 'fas fa-newspaper', 3],
            ['home_service', 'home_service.academique', null, null, null, null, null, null, null, 1],
            ['home_service', 'home_service.administration', null, null, null, null, null, null, null, 2],
            ['home_service', 'home_service.recherche', null, null, null, null, null, null, null, 3],
            ['home_service', 'home_service.orientation', null, null, null, null, null, null, null, 4],
            ['admission_step', 'admission_step.info', 'Informations', 'Lire les conditions avant de commencer.', 'Les informations publiees ici sont gerees depuis l administration.', null, null, null, 'fas fa-circle-info', 1],
            ['admission_step', 'admission_step.account', 'Creation de compte', 'Email, noms, telephone et mot de passe.', 'Le compte permet a l etudiant de se connecter au portail.', null, null, null, 'fas fa-user-plus', 2],
            ['admission_step', 'admission_step.form', 'Dossier', 'Une seule inscription par compte.', 'Apres l envoi du dossier, le compte accede au portefeuille etudiant.', null, null, null, 'fas fa-file-signature', 3],
            ['admission_step', 'admission_step.wallet', 'Portefeuille', 'Paiements, documents, inscriptions et commentaires.', 'Le portefeuille reste accessible apres chaque connexion.', null, null, null, 'fas fa-wallet', 4],
            ['admission_intro', 'admission_intro.documents', 'Pieces a preparer', 'Diplome, photo, identite, adresse et contacts.', 'Texte a completer par les vraies pieces exigees.', null, null, null, 'fas fa-list-check', 1],
            ['admission_intro', 'admission_intro.sections', 'Sections disponibles', 'Gestion commerciale et administrative, Gestion informatique.', 'Ces sections viennent aussi du module Sections et filieres.', null, null, null, 'fas fa-layer-group', 2],
            ['admission_intro', 'admission_intro.backend', 'Connexion backend', 'Les donnees sont envoyees aux API Laravel.', 'Ce bloc rappelle que le site est pret pour la base de donnees institutionnelle.', null, null, null, 'fas fa-database', 3],
        ];

        foreach ($blocks as [$group, $key, $title, $subtitle, $body, $imageUrl, $linkUrl, $linkLabel, $icon, $order]) {
            ContentBlock::updateOrCreate(
                ['key' => $key],
                [
                    'block_group' => $group,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'body' => $body,
                    'image_url' => $imageUrl,
                    'link_url' => $linkUrl,
                    'link_label' => $linkLabel,
                    'icon' => $icon,
                    'sort_order' => $order,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedContent(): void
    {
        // Les actualites, publications et evenements publics doivent venir de l'administration.
    }

    private function seedUsersAndStudentWallet(): void
    {
        $adminPassword = env('ADMIN_PASSWORD');

        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@isc-kindu.test')],
            [
                'name' => env('ADMIN_NAME', 'Administrateur ISC KINDU'),
                'first_name' => env('ADMIN_FIRST_NAME', 'Admin'),
                'last_name' => env('ADMIN_LAST_NAME', 'ISC KINDU'),
                'phone' => env('ADMIN_PHONE', '+243000000001'),
                'role' => 'admin',
                'status' => 'active',
                'institution_code' => 'ISC_KINDU',
                'password' => $adminPassword ?: 'password',
            ]
        );

        $admin->forceFill([
            'role' => 'admin',
            'status' => 'active',
            'institution_code' => 'ISC_KINDU',
        ]);

        if ($adminPassword) {
            $admin->password = $adminPassword;
        }

        $admin->save();

        if (! app()->environment('testing')) {
            $admin->tokens()->delete();
            return;
        }

        $student = User::firstOrCreate(
            ['email' => 'etudiant@isc-kindu.test'],
            [
                'name' => 'Etudiant Test',
                'first_name' => 'Etudiant',
                'last_name' => 'Test',
                'phone' => '+243000000002',
                'role' => 'student',
                'status' => 'active',
                'institution_code' => 'ISC_KINDU',
                'password' => 'password',
            ]
        );

        $section = Section::where('slug', 'gestion-commerciale-et-administrative')->first();
        $program = Program::where('slug', 'comptabilite-et-finances')->first();
        $academicYear = AcademicYear::where('is_current', true)->first();
        $level = Level::where('code', 'L1')->first();
        $promotion = Promotion::where('code', 'L1')->first();

        $application = AdmissionApplication::updateOrCreate(
            ['application_number' => 'ISC-TEST-0001'],
            [
                'user_id' => $student->id,
                'section_id' => $section?->id,
                'program_id' => $program?->id,
                'academic_year_id' => $academicYear?->id,
                'level_id' => $level?->id,
                'promotion_id' => $promotion?->id,
                'status' => 'submitted',
                'academic_year' => $this->academicYear(),
                'level' => 'L1',
                'last_name' => 'Test',
                'post_name' => 'Postnom',
                'first_name' => 'Etudiant',
                'gender' => 'M',
                'email' => $student->email,
                'phone' => $student->phone,
                'address' => 'Adresse test, Kindu',
                'birth_place' => 'Kindu',
                'last_school' => 'Ecole test',
                'diploma_year' => (int) now()->year - 1,
                'percentage' => 70,
                'guardian_phone' => '+243000000003',
                'comment' => 'Dossier test.',
                'submitted_at' => now(),
            ]
        );

        Payment::updateOrCreate(
            ['reference' => 'PAY-ISC-TEST-0001-INS'],
            [
                'user_id' => $student->id,
                'admission_application_id' => $application->id,
                'label' => 'Frais d inscription test',
                'amount' => 0,
                'currency' => 'CDF',
                'status' => 'pending',
                'due_date' => now()->addMonth()->toDateString(),
            ]
        );

        StudentDocument::updateOrCreate(
            [
                'user_id' => $student->id,
                'admission_application_id' => $application->id,
                'type' => 'fiche-inscription',
            ],
            [
                'name' => 'Fiche d inscription test',
                'status' => 'available',
                'issued_at' => now(),
            ]
        );

        StudentComment::updateOrCreate(
            [
                'user_id' => $student->id,
                'subject' => 'Commentaire test',
            ],
            [
                'message' => 'Message de test pour verifier le portail etudiant.',
                'status' => 'open',
            ]
        );

        $admin->tokens()->delete();
        $student->tokens()->delete();
    }

    private function academicYear(): string
    {
        $year = (int) now()->format('Y');

        return $year.'-'.($year + 1);
    }
}
