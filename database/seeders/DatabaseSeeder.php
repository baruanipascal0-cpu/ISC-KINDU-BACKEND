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
        $items = [
            ['Accueil', '/', 1],
            ['Sections et filieres', '/facultes-et-entites.html', 2],
            ['Diplome', '/diplomes.html', 3],
            ['Inscription', '/inscriptions.html', 4],
            ['Publications', '/publications.html', 5],
            ['Contact', '/contact.html', 6],
        ];

        foreach ($items as [$label, $url, $order]) {
            MenuItem::firstOrCreate(
                ['location' => 'main', 'label' => $label],
                [
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
            [
                'title' => 'Accueil',
                'slug' => 'accueil',
                'excerpt' => 'Page d accueil institutionnelle ISC KINDU.',
                'body' => 'Contenu a completer depuis l espace administrateur.',
                'image_url' => '/images/site/photo-1.jpg',
            ],
            [
                'title' => 'Inscription',
                'slug' => 'inscription',
                'excerpt' => 'Informations pour guider le candidat avant son inscription.',
                'body' => 'Le candidat cree un compte, se connecte, remplit le dossier et accede ensuite a son portefeuille etudiant.',
                'image_url' => '/images/site/photo-2.jpg',
            ],
            [
                'title' => 'Sections et filieres',
                'slug' => 'sections-et-filieres',
                'excerpt' => 'Sections organisees a ISC KINDU.',
                'body' => 'Cette page expose uniquement les sections et filieres organisees a ISC KINDU.',
                'image_url' => '/images/site/photo-3.jpg',
            ],
            [
                'title' => 'Diplomes',
                'slug' => 'diplomes',
                'excerpt' => 'Informations sur les diplomes et documents academiques.',
                'body' => 'Cette page sera alimentee par les communiques et fichiers de type Diplome publies dans l espace administrateur.',
                'image_url' => '/images/site/photo-4.jpg',
            ],
            [
                'title' => 'Ressources',
                'slug' => 'ressources',
                'excerpt' => 'Documents utiles et ressources a publier pour les visiteurs.',
                'body' => 'Les ressources ajoutees dans le backend apparaitront sur les pages bibliotheques, articles et ressources.',
                'image_url' => '/images/site/photo-5.jpg',
            ],
            [
                'title' => 'In memoriam',
                'slug' => 'in-memoriam',
                'excerpt' => 'Espace de communiques memoriels.',
                'body' => 'Les contenus in memoriam doivent etre publies depuis le backend avec le type In memoriam.',
                'image_url' => '/images/site/photo-6.jpg',
            ],
            [
                'title' => 'Alumni ISC',
                'slug' => 'alumni',
                'excerpt' => 'Espace communautaire des anciens etudiants ISC KINDU.',
                'body' => 'La page alumni publique fonctionne comme espace communautaire etudiant.',
                'image_url' => '/images/site/photo-7.jpg',
            ],
            [
                'title' => 'Contact',
                'slug' => 'contact',
                'excerpt' => 'Coordonnees et formulaire de contact ISC KINDU.',
                'body' => 'Les messages envoyes depuis le site sont enregistres dans le backend et consultables dans l espace administrateur.',
                'image_url' => '/images/site/photo-8.jpg',
            ],
            [
                'title' => 'Plan strategique',
                'slug' => 'plan-strategique',
                'excerpt' => 'Page institutionnelle a completer si elle est necessaire.',
                'body' => 'Ce contenu reste disponible dans le backend, mais les anciens liens lies a un autre etablissement sont masques sur le site.',
                'image_url' => '/images/site/photo-9.jpg',
            ],
        ];

        foreach ($pages as $page) {
            Page::firstOrCreate(
                ['slug' => $page['slug']],
                $page + ['is_published' => true]
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
            ['home_slide', 'home_slide.admissions', 'Inscriptions en ligne', 'Compte, dossier et portefeuille etudiant.', 'Texte a completer pour presenter les inscriptions.', '/images/site/photo-1.jpg', '/inscriptions.html', 'Commencer', 'fas fa-file-signature', 2],
            ['home_card', 'home_card.inscriptions', 'Inscriptions', 'Informations utiles pour demarrer une inscription.', 'Ce bloc peut etre modifie dans l admin.', '/images/site/photo-2.jpg', '/inscriptions.html', 'S inscrire', 'fas fa-user-plus', 1],
            ['home_card', 'home_card.sections', 'Sections et filieres', 'Apercu des sections organisees a l ISC KINDU.', 'Ce bloc mene vers les sections gerees depuis le backend.', '/images/site/photo-3.jpg', '/facultes-et-entites.html', 'Voir les sections', 'fas fa-layer-group', 2],
            ['home_card', 'home_card.publications', 'Publications', 'Communiques et documents officiels.', 'Ce bloc mene vers les publications admin.', '/images/site/photo-1.jpg', '/articles.html', 'Voir les publications', 'fas fa-newspaper', 3],
            ['home_service', 'home_service.academique', 'Service academique', 'Informations a completer.', 'Bloc de service modifiable depuis l admin.', null, '/presentation-de-lisc-kindu.html', 'Voir', 'fas fa-building-columns', 1],
            ['home_service', 'home_service.administration', 'Administration', 'Informations a completer.', 'Bloc de service modifiable depuis l admin.', null, '/contact.html', 'Voir', 'fas fa-briefcase', 2],
            ['home_service', 'home_service.recherche', 'Recherche', 'Informations a completer.', 'Bloc de service modifiable depuis l admin.', null, '/centre-et-instituts-de-recherche.html', 'Voir', 'fas fa-book-open', 3],
            ['home_service', 'home_service.orientation', 'Orientation', 'Informations a completer.', 'Bloc de service modifiable depuis l admin.', null, '/inscriptions.html', 'Voir', 'fas fa-comments', 4],
            ['admission_step', 'admission_step.info', 'Informations', 'Lire les conditions avant de commencer.', 'Les informations publiees ici sont gerees depuis l administration.', null, null, null, 'fas fa-circle-info', 1],
            ['admission_step', 'admission_step.account', 'Creation de compte', 'Email, noms, telephone et mot de passe.', 'Le compte permet a l etudiant de se connecter au portail.', null, null, null, 'fas fa-user-plus', 2],
            ['admission_step', 'admission_step.form', 'Dossier', 'Une seule inscription par compte.', 'Apres l envoi du dossier, le compte accede au portefeuille etudiant.', null, null, null, 'fas fa-file-signature', 3],
            ['admission_step', 'admission_step.wallet', 'Portefeuille', 'Paiements, documents, inscriptions et commentaires.', 'Le portefeuille reste accessible apres chaque connexion.', null, null, null, 'fas fa-wallet', 4],
            ['admission_intro', 'admission_intro.documents', 'Pieces a preparer', 'Diplome, photo, identite, adresse et contacts.', 'Texte a completer par les vraies pieces exigees.', null, null, null, 'fas fa-list-check', 1],
            ['admission_intro', 'admission_intro.sections', 'Sections disponibles', 'Gestion commerciale et administrative, Gestion informatique.', 'Ces sections viennent aussi du module Sections et filieres.', null, null, null, 'fas fa-layer-group', 2],
            ['admission_intro', 'admission_intro.backend', 'Connexion backend', 'Les donnees sont envoyees aux API Laravel.', 'Ce bloc rappelle que le site est pret pour la base de donnees institutionnelle.', null, null, null, 'fas fa-database', 3],
        ];

        foreach ($blocks as [$group, $key, $title, $subtitle, $body, $imageUrl, $linkUrl, $linkLabel, $icon, $order]) {
            ContentBlock::firstOrCreate(
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
        $admin = User::firstOrCreate(
            ['email' => 'admin@isc-kindu.test'],
            [
                'name' => 'Administrateur ISC KINDU',
                'first_name' => 'Admin',
                'last_name' => 'ISC KINDU',
                'phone' => '+243000000001',
                'role' => 'admin',
                'status' => 'active',
                'institution_code' => 'ISC_KINDU',
                'password' => 'password',
            ]
        );

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
