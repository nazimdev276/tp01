// js/script.js : JavaScript pur (aucune bibliothèque)

document.addEventListener('DOMContentLoaded', function () {
    var formPersonne = document.getElementById('form-personne');
    if (formPersonne) initPersonne(formPersonne);

    var formNat = document.getElementById('form-nationalite');
    if (formNat) initNationalite(formNat);

    var formTransport = document.getElementById('form-transport');
    if (formTransport) initTransport(formTransport);

    var formSport = document.getElementById('form-sport');
    if (formSport) initSport(formSport);

    var formFiliere = document.getElementById('form-filiere');
    if (formFiliere) initFiliere(formFiliere);

    var formEnseignant = document.getElementById('form-enseignant');
    if (formEnseignant) initEnseignant(formEnseignant);

    var formModule = document.getElementById('form-module');
    if (formModule) initModule(formModule);
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
    if (!form.transport.value) erreurs.push('Aucun moyen de transport disponible.');
    if (!form.filiere || !form.filiere.value) erreurs.push('Veuillez choisir une filière d’inscription.');
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
    var filiereTexte = '';
    if (form.filiere) {
        filiereTexte = form.filiere.options[form.filiere.selectedIndex].text;
    }
    var lignes = [
        ['Numéro', form.numero.value],
        ['Civilité', form.civilite.value],
        ['Nom / Prénom', form.nom_prenom.value],
        ['Adresse', form.adresse.value],
        ['No postal / Localité', form.code_postal.value + ' ' + form.localite.value],
        ['Pays', form.pays.value],
        ['Plateforme(s)', valeursCochees(form, 'plateformes[]')],
        ['Module(s)', valeursCochees(form, 'modules[]')],
        ['Application(s)', valeursCochees(form, 'applications[]')],
        ['Voiture(s)', Array.prototype.map.call(form.querySelectorAll('select[name="voiture[]"] option:checked'), function (option) {
            return option.value;
        }).join(', ')],
        ['Moyen de transport', form.transport.value],
        ['Groupe sanguin', form.groupe_sanguin.value],
        ['Sport', form.sport.value],
        ['Activité(s)', valeursCochees(form, 'activites[]')],
        ['Nationalité', natTexte],
        ['Filière d’inscription', filiereTexte]
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

function initTransport(form) {
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var libelle = form.libelle.value.trim();
        if (libelle === '' || libelle.length > 20) {
            e.preventDefault();
            alert('Le moyen de transport est obligatoire (20 caractères maximum).');
        }
    });
}

function initSport(form) {
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var libelle = form.libelle.value.trim();
        if (libelle === '' || libelle.length > 40) {
            e.preventDefault();
            alert('Le sport est obligatoire (40 caractères maximum).');
        }
    });
}

function initFiliere(form) {
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var code = form.code.value.trim();
        var libelle = form.libelle.value.trim();
        if (code === '' || code.length > 10 || libelle === '' || libelle.length > 10) {
            e.preventDefault();
            alert('Code : 1 à 10 caractères. Filière : 1 à 10 caractères.');
        }
    });
}

function initEnseignant(form) {
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var id = form.elements.namedItem('id').value.trim();
        if ((id !== '' && !/^[1-9][0-9]*$/.test(id)) ||
            !/^[0-9]{1,10}$/.test(form.numero.value.trim()) ||
            form.nom_prenom.value.trim() === '' ||
            form.adresse.value.trim() === '' ||
            form.date_naissance.value === '' ||
            form.lieu_naissance.value.trim() === '' ||
            form.pays.value === '' ||
            form.grade.value === '' ||
            form.specialite.value === '') {
            e.preventDefault();
            alert('Veuillez renseigner correctement tous les champs obligatoires. L’ID, s’il est saisi, doit être un entier positif.');
        }
    });
}

function initModule(form) {
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var id = form.elements.namedItem('id').value.trim();
        var coefficient = Number(form.coefficient.value);
        var volumeHoraire = form.volume_horaire.value.trim();
        if ((id !== '' && !/^[1-9][0-9]*$/.test(id)) ||
            form.code.value.trim() === '' ||
            form.designation.value.trim() === '' ||
            !Number.isFinite(coefficient) || coefficient <= 0 || coefficient > 999.99 ||
            !/^(0|[1-9][0-9]{0,4})$/.test(volumeHoraire) || Number(volumeHoraire) > 65535 ||
            form.filiere.value === '') {
            e.preventDefault();
            alert('Veuillez renseigner correctement tous les champs obligatoires. L’ID, s’il est saisi, doit être un entier positif.');
        }
    });
}
