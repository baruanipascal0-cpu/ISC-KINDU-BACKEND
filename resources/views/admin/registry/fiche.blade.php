<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche d inscription {{ $enrollment->enrollment_number }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 36px; }
        .header { display: flex; align-items: center; gap: 16px; border-bottom: 2px solid #b20d19; padding-bottom: 14px; margin-bottom: 24px; }
        .header img { width: 72px; height: 72px; object-fit: contain; }
        h1 { color: #b20d19; margin: 0; font-size: 24px; }
        h2 { color: #073b7a; font-size: 18px; margin-top: 28px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 10px; text-align: left; }
        th { width: 32%; background: #f3f4f6; }
        .footer { margin-top: 42px; display: flex; justify-content: space-between; gap: 40px; }
        .signature { width: 240px; border-top: 1px solid #111827; padding-top: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <img src="/images/site/logo.jpg" alt="ISC KINDU">
        <div>
            <h1>ISC KINDU</h1>
            <div>Fiche officielle d inscription</div>
        </div>
    </div>

    <h2>Identification</h2>
    <table>
        <tr><th>Numero d inscription</th><td>{{ $enrollment->enrollment_number }}</td></tr>
        <tr><th>Matricule</th><td>{{ $student->matricule }}</td></tr>
        <tr><th>Nom</th><td>{{ $student->last_name }}</td></tr>
        <tr><th>Postnom</th><td>{{ $student->post_name }}</td></tr>
        <tr><th>Prenom</th><td>{{ $student->first_name }}</td></tr>
        <tr><th>Sexe</th><td>{{ $student->gender }}</td></tr>
        <tr><th>Telephone</th><td>{{ $student->phone }}</td></tr>
        <tr><th>Email</th><td>{{ $student->email }}</td></tr>
    </table>

    <h2>Choix academique</h2>
    <table>
        <tr><th>Annee academique</th><td>{{ $academicYear->code }}</td></tr>
        <tr><th>Section</th><td>{{ $enrollment->section?->name }}</td></tr>
        <tr><th>Filiere</th><td>{{ $enrollment->program?->name }}</td></tr>
        <tr><th>Niveau</th><td>{{ $level?->name }}</td></tr>
        <tr><th>Promotion</th><td>{{ $promotion?->name }}</td></tr>
        <tr><th>Type</th><td>{{ $enrollment->type }}</td></tr>
        <tr><th>Date</th><td>{{ $enrollment->enrolled_on?->format('d/m/Y') }}</td></tr>
    </table>

    <div class="footer">
        <div class="signature">Etudiant</div>
        <div class="signature">Administration</div>
    </div>
</body>
</html>
