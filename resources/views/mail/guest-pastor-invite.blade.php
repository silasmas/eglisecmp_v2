<x-mail::message>
# Bienvenue au Centre Missionnaire Philadelphie

Bonjour **{{ $pastorName }}**,

Afin de mieux préparer votre accueil dans le cadre de **{{ $projectTitle }}**, merci de remplir la fiche de renseignements :

**{{ $formTitle }}**

<x-mail::button :url="$formUrl">
Remplir la fiche
</x-mail::button>

Ce lien vous est personnel. Merci de le compléter dès que possible.

Cordialement,<br>
Centre Missionnaire Philadelphie
</x-mail::message>
