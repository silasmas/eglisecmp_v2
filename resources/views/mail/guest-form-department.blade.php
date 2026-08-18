<x-mail::message>
# Fiche renseignement reçue

Bonjour **{{ $departmentName }}**,

Le pasteur **{{ $pastorName }}** a rempli la fiche de renseignements pour le projet **{{ $projectTitle }}** ({{ $submittedAt }}).

Consultez les réponses liées à votre département :

<x-mail::button :url="$portalUrl">
Voir les réponses
</x-mail::button>

**Mot de passe du formulaire :** `{{ $passwordHint }}`

Ce lien est personnel à cette soumission. Saisissez le mot de passe pour afficher les informations qui vous concernent.

Cordialement,<br>
Centre Missionnaire Philadelphie
</x-mail::message>
