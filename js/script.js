// js/script.js : JavaScript pur (aucune bibliothèque)

document.addEventListener('DOMContentLoaded', function () {
    var formPersonne = document.getElementById('form-personne');
    if (formPersonne) initPersonne(formPersonne);

    var formNat = document.getElementById('form-nationalite');
    if (formNat) initNationalite(formNat);
});

// ---------- Formulaire Personne ----------
function initPersonne(form) {

    // Validation avant envoi (sauf pour les boutons marqués "formnovalidate")
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var erreurs = validerPersonne(form);
        if (erreurs.length > 0) {
            e.preventDefault();
            alert(erreurs.join('\n'));
        }
    });

    // Bouton "Affichage JavaScript" : affiche les valeurs sans recharger la page
    document.getElementById('btn-affichage-js').addEventListener('click', function () {
        afficherValeurs(form);
    });

    // Aperçu de la photo choisie
    document.getElementById('photo').addEventListener('change', function () {
        var apercu = document.getElementById('apercu');
        if (this.files.length > 0) {
            apercu.src = URL.createObjectURL(this.files[0]);
            apercu.style.display = 'block';
        } else {
            apercu.style.display = 'none';
        }
    });
}

function validerPersonne(form) {
    var erreurs = [];
    if (!/^[0-9]{1,10}$/.test(form.numero.value.trim())) erreurs.push('Numéro invalide (chiffres uniquement).');
    if (form.nom_prenom.value.trim() === '') erreurs.push('Le nom / prénom est obligatoire.');
    if (form.adresse.value.trim() === '') erreurs.push("L'adresse est obligatoire.");
    if (!/^[0-9]{4,10}$/.test(form.code_postal.value.trim())) erreurs.push('Code postal invalide.');
    if (form.localite.value.trim() === '') erreurs.push('La localité est obligatoire.');
    if (!form.nationalite) erreurs.push("Aucune nationalité disponible : ajoutez-en une d'abord.");
    return erreurs;
}

// Retourne les valeurs cochées d'un groupe de cases à cocher
function valeursCochees(form, nom) {
    var cases = form.querySelectorAll('input[name="' + nom + '"]:checked');
    var valeurs = [];
    for (var i = 0; i < cases.length; i++) valeurs.push(cases[i].value);
    return valeurs.join(', ');
}

function afficherValeurs(form) {
    var natTexte = '';
    if (form.nationalite) {
        natTexte = form.nationalite.options[form.nationalite.selectedIndex].text;
    }
    var lignes = [
        ['Numéro', form.numero.value],
        ['Civilité', form.civilite.value],
        ['Nom / Prénom', form.nom_prenom.value],
        ['Adresse', form.adresse.value],
        ['No postal / Localité', form.code_postal.value + ' ' + form.localite.value],
        ['Pays', form.pays.value],
        ['Plateforme(s)', valeursCochees(form, 'plateformes[]')],
        ['Application(s)', valeursCochees(form, 'applications[]')],
        ['Nationalité', natTexte]
    ];

    var zone = document.getElementById('resultat_js');
    zone.textContent = '';                       // on vide la zone
    var titre = document.createElement('strong');
    titre.textContent = 'Affichage JavaScript';
    zone.appendChild(titre);

    for (var i = 0; i < lignes.length; i++) {
        var p = document.createElement('div');
        p.textContent = lignes[i][0] + ' : ' + lignes[i][1];   // textContent = pas de risque XSS
        zone.appendChild(p);
    }
    zone.style.display = 'block';
}

// ---------- Formulaire Nationalité ----------
function initNationalite(form) {
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var code = form.code.value.trim();
        var libelle = form.libelle.value.trim();
        if (code === '' || code.length > 3 || libelle === '' || libelle.length > 40) {
            e.preventDefault();
            alert('Code : 1 à 3 caractères. Nationalité : 1 à 40 caractères.');
        }
    });
}
