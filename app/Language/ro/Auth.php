<?php

declare(strict_types=1);

// Overrides vendor/codeigniter4/shield's own en-only Auth.php - app/Language
// takes priority over vendor in CI4's language file resolution, so this is
// picked up automatically for locale=ro without touching the vendor package.
return [
    // Exceptions
    'unknownAuthenticator'  => '{0} nu este un autentificator valid.',
    'unknownUserProvider'   => 'Nu s-a putut determina furnizorul de utilizatori.',
    'invalidUser'           => 'Utilizatorul specificat nu a fost găsit.',
    'bannedUser'            => 'Nu te poți autentifica - contul este blocat.',
    'logOutBannedUser'      => 'Ai fost deconectat deoarece contul a fost blocat.',
    'badAttempt'            => 'Autentificare eșuată. Verifică datele introduse.',
    'noPassword'            => 'Nu se poate valida un utilizator fără parolă.',
    'invalidPassword'       => 'Autentificare eșuată. Verifică parola.',
    'noToken'               => 'Fiecare cerere trebuie să conțină un token în header-ul {0}.',
    'badToken'              => 'Token-ul de acces este invalid.',
    'oldToken'               => 'Token-ul de acces a expirat.',
    'noUserEntity'          => 'Este necesară o entitate User pentru validarea parolei.',
    'invalidEmail'          => 'Adresa de email „{0}” nu corespunde celei din evidență.',
    'unableSendEmailToUser' => 'Ne pare rău, a apărut o problemă la trimiterea email-ului către „{0}”.',
    'throttled'              => 'Prea multe cereri de la această adresă IP. Poți reîncerca peste {0} secunde.',
    'notEnoughPrivilege'    => 'Nu ai permisiunea necesară pentru această operațiune.',
    // JWT Exceptions
    'invalidJWT'     => 'Token-ul este invalid.',
    'expiredJWT'     => 'Token-ul a expirat.',
    'beforeValidJWT' => 'Token-ul nu este încă valabil.',

    'email'           => 'Adresă de email',
    'username'        => 'Nume utilizator',
    'password'        => 'Parolă',
    'passwordConfirm' => 'Parolă (din nou)',
    'haveAccount'     => 'Ai deja un cont?',
    'token'           => 'Token',

    // Buttons
    'confirm' => 'Confirmă',
    'send'    => 'Trimite',

    // Registration
    'register'         => 'Înregistrare',
    'registerDisabled' => 'Înregistrarea nu este permisă momentan.',
    'registerSuccess'  => 'Bine ai venit!',

    // Login
    'login'              => 'Autentificare',
    'needAccount'        => 'Nu ai cont?',
    'rememberMe'         => 'Ține-mă minte',
    'forgotPassword'     => 'Ai uitat parola?',
    'useMagicLink'       => 'Folosește un link de autentificare',
    'magicLinkSubject'   => 'Link-ul tău de autentificare',
    'magicTokenNotFound' => 'Link-ul nu a putut fi verificat.',
    'magicLinkExpired'   => 'Ne pare rău, link-ul a expirat.',
    'checkYourEmail'     => 'Verifică-ți email-ul!',
    'magicLinkDetails'   => 'Ți-am trimis un email cu un link de autentificare. Este valabil doar {0} minute.',
    'magicLinkDisabled'  => 'Autentificarea prin link nu este permisă momentan.',
    'successLogout'      => 'Te-ai deconectat cu succes.',
    'backToLogin'        => 'Înapoi la autentificare',

    // Passwords
    'errorPasswordLength'       => 'Parola trebuie să aibă cel puțin {0, number} caractere.',
    'suggestPasswordLength'     => 'O frază - de până la 255 de caractere - e o parolă mai sigură și mai ușor de reținut.',
    'errorPasswordCommon'       => 'Parola nu poate fi una comună.',
    'suggestPasswordCommon'     => 'Parola a fost verificată împotriva a peste 65.000 de parole comune sau expuse în incidente de securitate.',
    'errorPasswordPersonal'     => 'Parola nu poate conține date personale re-codificate.',
    'suggestPasswordPersonal'   => 'Evită să folosești variații ale adresei de email sau numelui de utilizator ca parolă.',
    'errorPasswordTooSimilar'   => 'Parola este prea asemănătoare cu numele de utilizator.',
    'suggestPasswordTooSimilar' => 'Nu folosi părți din numele de utilizator în parolă.',
    'errorPasswordPwned'        => 'Parola {0} a fost expusă într-o breșă de securitate și a fost văzută de {1, number} ori în {2} din parolele compromise.',
    'suggestPasswordPwned'      => '{0} nu ar trebui folosită niciodată ca parolă. Dacă o folosești undeva, schimb-o imediat.',
    'errorPasswordEmpty'        => 'Parola este obligatorie.',
    'errorPasswordTooLongBytes' => 'Parola nu poate depăși {param} octeți.',
    'passwordChangeSuccess'     => 'Parola a fost schimbată cu succes',
    'userDoesNotExist'          => 'Parola nu a fost schimbată. Utilizatorul nu există',
    'resetTokenExpired'         => 'Ne pare rău, token-ul de resetare a expirat.',

    // Email Globals
    'emailInfo'      => 'Câteva informații despre persoană:',
    'emailIpAddress' => 'Adresă IP:',
    'emailDevice'    => 'Dispozitiv:',
    'emailDate'      => 'Dată:',

    // 2FA
    'email2FATitle'       => 'Autentificare în doi pași',
    'confirmEmailAddress' => 'Confirmă adresa de email.',
    'emailEnterCode'      => 'Confirmă email-ul',
    'emailConfirmCode'    => 'Introdu codul din 6 cifre trimis pe adresa ta de email.',
    'email2FASubject'     => 'Codul tău de autentificare',
    'email2FAMailBody'    => 'Codul tău de autentificare este:',
    'invalid2FAToken'     => 'Codul introdus este incorect.',
    'need2FA'             => 'Trebuie să finalizezi verificarea în doi pași.',
    'needVerification'    => 'Verifică-ți email-ul pentru a finaliza activarea contului.',

    // Activate
    'emailActivateTitle'    => 'Activare cont',
    'emailActivateBody'     => 'Ți-am trimis un email cu un cod pentru confirmarea adresei. Copiază codul și introdu-l mai jos.',
    'emailActivateSubject'  => 'Codul tău de activare',
    'emailActivateMailBody' => 'Folosește codul de mai jos pentru a-ți activa contul.',
    'invalidActivateToken'  => 'Codul introdus este incorect.',
    'needActivate'          => 'Trebuie să finalizezi înregistrarea confirmând codul trimis pe email.',
    'activationBlocked'     => 'Trebuie să-ți activezi contul înainte de autentificare.',

    // Groups
    'unknownGroup' => '{0} nu este un grup valid.',
    'missingTitle' => 'Grupurile trebuie să aibă un titlu.',

    // Permissions
    'unknownPermission' => '{0} nu este o permisiune validă.',
];
