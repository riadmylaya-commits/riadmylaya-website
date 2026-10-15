<?php
/**
 * Riad Bilkis — messages du formulaire de contact (Contact Form 7) en FR / EN / ES.
 *
 * Le formulaire de contact est unique et sert les trois URL (/contact/,
 * /en/contact/, /es/contacto/) : ses messages de confirmation, d'erreur et de
 * validation sont donc traduits au moment de l'affichage, selon la langue de
 * l'URL, comme les autres formulaires du site.
 */

if (!defined('ABSPATH')) exit;

function riad_bilkis_cf7_messages() {
    return array(
        'mail_sent_ok' => array(
            'fr' => 'Merci ! Votre message a bien été envoyé. Nous vous répondons rapidement.',
            'en' => 'Thank you! Your message has been sent. We will reply shortly.',
            'es' => '¡Gracias! Su mensaje ha sido enviado. Le responderemos en breve.',
        ),
        'mail_sent_ng' => array(
            'fr' => "L'envoi a échoué. Écrivez-nous sur WhatsApp ou à riadbilkis@gmail.com.",
            'en' => 'Sending failed. Please write to us on WhatsApp or at riadbilkis@gmail.com.',
            'es' => 'El envío ha fallado. Escríbanos por WhatsApp o a riadbilkis@gmail.com.',
        ),
        'spam' => array(
            'fr' => "L'envoi a échoué. Écrivez-nous sur WhatsApp ou à riadbilkis@gmail.com.",
            'en' => 'Sending failed. Please write to us on WhatsApp or at riadbilkis@gmail.com.',
            'es' => 'El envío ha fallado. Escríbanos por WhatsApp o a riadbilkis@gmail.com.',
        ),
        'validation_error' => array(
            'fr' => 'Un ou plusieurs champs sont incomplets. Merci de vérifier et de réessayer.',
            'en' => 'One or more fields have an error. Please check and try again.',
            'es' => 'Uno o varios campos contienen un error. Compruébelos e inténtelo de nuevo.',
        ),
        'accept_terms' => array(
            'fr' => 'Merci d\'accepter les conditions avant d\'envoyer votre message.',
            'en' => 'You must accept the terms and conditions before sending your message.',
            'es' => 'Debe aceptar las condiciones antes de enviar su mensaje.',
        ),
        'invalid_required' => array(
            'fr' => 'Merci de compléter ce champ.',
            'en' => 'Please fill out this field.',
            'es' => 'Complete este campo, por favor.',
        ),
        'invalid_email' => array(
            'fr' => 'Merci de saisir une adresse e-mail valide.',
            'en' => 'Please enter a valid email address.',
            'es' => 'Introduzca una dirección de correo electrónico válida.',
        ),
        'invalid_tel' => array(
            'fr' => 'Merci de saisir un numéro de téléphone valide.',
            'en' => 'Please enter a valid phone number.',
            'es' => 'Introduzca un número de teléfono válido.',
        ),
        'invalid_url' => array(
            'fr' => 'Merci de saisir une adresse web valide.',
            'en' => 'Please enter a valid URL.',
            'es' => 'Introduzca una dirección web válida.',
        ),
        'invalid_date' => array(
            'fr' => 'Merci de saisir une date au format AAAA-MM-JJ.',
            'en' => 'Please enter a date in YYYY-MM-DD format.',
            'es' => 'Introduzca una fecha con el formato AAAA-MM-DD.',
        ),
        'date_too_early' => array(
            'fr' => 'La date indiquée est trop proche.',
            'en' => 'The date is too early.',
            'es' => 'La fecha indicada es demasiado temprana.',
        ),
        'date_too_late' => array(
            'fr' => 'La date indiquée est trop éloignée.',
            'en' => 'The date is too late.',
            'es' => 'La fecha indicada es demasiado lejana.',
        ),
        'invalid_number' => array(
            'fr' => 'Merci de saisir un nombre.',
            'en' => 'Please enter a number.',
            'es' => 'Introduzca un número.',
        ),
        'number_too_small' => array(
            'fr' => 'Le nombre indiqué est trop petit.',
            'en' => 'The number is too small.',
            'es' => 'El número indicado es demasiado pequeño.',
        ),
        'number_too_large' => array(
            'fr' => 'Le nombre indiqué est trop grand.',
            'en' => 'The number is too large.',
            'es' => 'El número indicado es demasiado grande.',
        ),
        'invalid_too_long' => array(
            'fr' => 'Le texte saisi est trop long.',
            'en' => 'This field has a too long input.',
            'es' => 'El texto introducido es demasiado largo.',
        ),
        'invalid_too_short' => array(
            'fr' => 'Le texte saisi est trop court.',
            'en' => 'This field has a too short input.',
            'es' => 'El texto introducido es demasiado corto.',
        ),
        'quiz_answer_not_correct' => array(
            'fr' => 'La réponse à la question est incorrecte.',
            'en' => 'The answer to the question is incorrect.',
            'es' => 'La respuesta a la pregunta es incorrecta.',
        ),
        'upload_failed' => array(
            'fr' => "L'envoi du fichier a échoué.",
            'en' => 'There was an unknown error uploading the file.',
            'es' => 'Se ha producido un error al subir el archivo.',
        ),
        'upload_file_type_invalid' => array(
            'fr' => "Ce type de fichier n'est pas autorisé.",
            'en' => 'You are not allowed to upload files of this type.',
            'es' => 'No se permite subir archivos de este tipo.',
        ),
        'upload_file_too_large' => array(
            'fr' => 'Le fichier envoyé est trop volumineux.',
            'en' => 'The uploaded file is too large.',
            'es' => 'El archivo enviado es demasiado grande.',
        ),
        'upload_failed_php_error' => array(
            'fr' => "L'envoi du fichier a échoué.",
            'en' => 'There was an error uploading the file.',
            'es' => 'Se ha producido un error al subir el archivo.',
        ),
    );
}

// L'envoi passe par l'API REST de Contact Form 7 : l'URL de la requête ne porte
// plus le préfixe /en/ ou /es/, la langue de la page est donc postée avec le
// formulaire.
add_filter('wpcf7_form_hidden_fields', 'riad_bilkis_cf7_hidden_lang');
function riad_bilkis_cf7_hidden_lang($fields) {
    $fields['rb_lang'] = riad_bilkis_lang();
    return $fields;
}

function riad_bilkis_cf7_lang() {
    if (isset($_POST['rb_lang']) && in_array($_POST['rb_lang'], array('fr', 'en', 'es'), true)) {
        return $_POST['rb_lang'];
    }
    return riad_bilkis_lang();
}

add_filter('wpcf7_display_message', 'riad_bilkis_cf7_display_message', 20, 2);
function riad_bilkis_cf7_display_message($message, $status) {
    $messages = riad_bilkis_cf7_messages();
    if (!isset($messages[$status])) return $message;
    $lang = riad_bilkis_cf7_lang();
    return isset($messages[$status][$lang]) ? $messages[$status][$lang] : $messages[$status]['fr'];
}
