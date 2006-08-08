<?php
/** 
  * Miniforum - plugin for BLOG:CMS and Nucleus CMS
  * 2005, (c) Josef Adamcik (blog.pepiino.info)
  *
  *
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * 
  *  
*/

/**
* Language file for NP_MiniForum. 
* language: czech (kodovani utf-8)
* version: 0.6.5
* Poznámky: - Pokud chcete mít přeloženy i popisky v nastavení pluginu a popis pluginu, musíte plugin
*           po změně souboru přeinstalovat.
*           - nové řetězce jsou od verze 0.5.0 přidávány na konec souboru
*/


//plugin description
define('MF_PLUGIN_DESCRIPTION', 'Tento plugin vam umoznuje pridat jednoduche forum (popr. fora) do vaseho blogu.'.
        'Pro vlozeni prispevku z fora jmenem "mojeforum" pouzijte zapis "<%MiniForum(ShowPosts,mojeforum)%>"'.
        'a "<%MiniForum(ShowForm,myforum)%>" k zobrazení formulare pro pridavani prispevku.'.
        'Plugin poskytuje spravce, ktery vam  umozni vytvaret/mazat/upravovat fora a prispevky.');

// plugin options 
define('MF_ENABLE_QICK_MENU',           'Zobrazit plugin v menu?');
define('MF_POSTS_TO_SHOW',              'Počet příspěvků, které se zobrazí na jedné stránce:');
define('MF_MAX_LINE_LENGTH',            'Maximální délka nezalamovaného slova v příspěvku. Delší slova budou rozdělena pomlčkou.');
define('MF_COVERT_EMOTICONS',           'Převádět smajlíky na obrázky? (<a href="http://wakka.xiffy.nl/miniforum#smileys">Více v dokumentaci</a>)');
define('MF_COVERT_URLS',                'Převádět url v textu na odkazy?');
define('MF_EMOTICONS_DIR',				'Cesta k adresáři se smajlíky.');
define('MF_COVERT_NL',                  'Převádět odřádkování na <br /> tagy');

//template options
define('MF_POST_LIST_HEADER',           'Hlavička seznamu komentářů');
define('MF_POST_BODY',                  'Tělo komentáře');
define('MF_POST_LIST_FOOT',             'Patička seznamu komentářů');
define('MF_FORM_LOGGED',                'Tělo formuláře pro přihlášené uživatele');
define('MF_FORM_NOTLOGGED',             'Tělo formuláře pro nepřihlášené uživatele');
define('MF_NAVIGATION',                 'Šablona pro navigaci (tag &lt;navigation%&gt;)');
define('MF_NAME_NOURL',                 'Šablona pro zobrazení tagu &lt;%name%&gt;, pokud uživatel nezadal mail ani www');
define('MF_NAME_URL',                   'Šablona pro zobrazení tagu &lt;%name%&gt;,pokud uživatel zadal www či mail');
define('MF_MEMBER_NAME',                'Šablona pro zobrazení tagu &lt;%name%&gt;, pro registrované uživatele.');
define('MF_DATE',                       'Šablona pro zobrazení data (jako pro <a href="http://php.net/date" title="Odkaz na php manuál">php funkci date()</a>)');
define('MF_TIME',                       'Šablona pro zobrazení času (stejně jako v předchozím)');
define('MF_NEXTPAGE',                   'Text pro odkaz na další stránku');
define('MF_PREVIOUSPAGE',               'Text pro odkaz na předešlou stránku');
define('MF_FIRSTPAGE',                  'Text pro odkaz na první stránku');
define('MF_LASTPAGE',                   'Text pro odkaz na poslední stránku');

// qick menu title and tooltip
define('MF_QM_TITLE',                   'Mini forum');
define('MF_QM_TOOLTIP',                 'Správa MiniFora');

//errors
define('MF_FORUM_DOESNT_EX',            'zvolené fórum neexistuje');
define('MF_UNKNOWN_OPTION',             'neznámá volba ');
define('MF_NAME_PROTECTED',             'Jméno \'$uname\' patří regisrivanému uživateli. Zvolte si prosím jiné, nebo se přihlašte.'); //instead of $uname will be insertet user name
define('MF_NAME_MISSING',               'Musíte zadat jméno!!');
define('MF_TEXT_MISSING',               'Musíte zadat text příspěvku!!');


// **** Admin Area ****

define('MF_ADMIN_AREA_HEADING',         'Mini forum - správce');
define('MF_FORUM_LIST_HEADING',         'Seznam fór');
define('MF_CREATE_FORUM_HEADING',       'Nové fórum');

//common
define('MF_FORUM',                      'Fórum');
define('MF_TITLE',                      'Název');
define('MF_DESCRIPTION',                'Popisek');
define('MF_POSTS',                      'příspěvky');
define('MF_ACTIONS',                    'Akce');
define('MF_SHOW',                       'zobrazit&nbsp;příspěvky');
define('MF_EDIT_INFO',                  'upravit&nbsp;informace');
define('MF_DELETE',                     'smazat');
define('MF_EDIT',                       'upravit');
define('MF_YES',                        'Ano');
define('MF_NO',                         'Ne');
define('MF_SHORT_NAME',                 'Krátké jméno');
define('MF_SHORT_NAME_CHARS',           '(pouze znaky: a-z,A-Z,0-9,-,_)');
define('MF_ERR','Error');
define('MF_NOT_LOGGED_IN',              'Nejste přihlášen');
define('MF_NOT_LOGGED_IN_UPGRADE',      'Není možno provést upgrade. Musíte být přihlášen(a) jako administrátor.');
define('MF_UPGRADED',                   'Upgrade proveden.');



//creating and managing forum
define('MF_CREATE_FORUM_BUTTON',        'Založ fórum');
define('MF_MISSING_SHORT_NAME',         'Musíte zvolit krátké jméno!'); //error message
define('MF_WRONG_SHORT_NAME',           'Pro krátké jméno můžete použít pouze následující znaky : 0-9,a-z,A-z,_,-'); //error message
define('MF_SHORT_NAME_USED',            'Fórum s tímto krátkým jménem již existuje. Prosím zvolte jiné');//error message
define('MF_CONFIRM_FORUM_DELETE',       'Chcete opravdu smazat toto fórum a všechny příspěvky v něm?');
define('MF_CHANGE_FORUM',               'Změna informací o fóru  \'$forum_name\''); //$forum_name will be replaced with forum short name
define('MF_CHANGE_FORUM_BUTTON',        'změnit');



// managing posts
define('MF_LISTED_POSTS',               'Příspěvky ve fóru'); //will be falowed by forum name
define('MF_FORUM_EMPTY',                'V tomto fóru (\'$forum_name\') nejsou zatím žádné příspěvky');  //$forum_name will be repleaced with forum short name
define('MF_PLIST_PREV',                 '&lt;&lt; Předchozí'); //button text
define('MF_PLIST_NEXT',                 'Další &gt;&gt;');//button text
define('MF_PLIST_CURRENT_PAGE',         'Stránka $current_page z $page_count '); //$current_page and $page_count will be repleaced with numbers
define('MF_PLIST_INF',                  'Informace'); //column title
define('MF_PLIST_ACTIONS',              'Akce'); //column title
define('MF_CONFIRM_POST_DELETE',        'Chcete opravdu smazat tento příspěvek?');
define('MF_POST_DELETED',               'Příspěvek smazán.');
define('MF_EDIT_POST',                  'Uprav příspěvek'); 
define('MF_POST_CHANGED',               'Příspěvek změněn');
define('MF_USER_NAME',                  'Jméno');
define('MF_USER_URL',                   'adresa (http nebo e-mail)');
define('MF_POST_BODY',                  'Text příspěvku');
define('MF_CHANGE_POST_BUTTON',         'Změnit');

//******************************************************************************
// Since 0.5.0
//******************************************************************************
//templates
define('MF_TEMPLATES',					'Šablony');
define('MF_TEMPLATE',					'Šablona');
define('MF_NEW_TEMPLATE',				'Nová šablona');
define('MF_COPY',						'Kopírovat');
define('MF_EDIT_TEMPL',					'Upravit');
define('MF_DEFAULT_TEMPLATE',			'šablona s implicitními hodnotami');
define('MF_CONFIRM_TEMPLATE_DELETE',	'Chcete opravdu smazat tuto šablonu?');
define('MF_TEMPLATE_NAME',				'Jméno šablony');
define('MF_CREATE_TEMPLATE_BUTTON',		'Vytvořit šablonu');
define('MF_TEMPLATE_CREATED',			'Nová šablona vytvořena.');
define('MF_TEMPLATE_CHANGED',			'Šablona změněna.');
define('MF_TEMPLATE_NAME_USED',			'Šablona tohot jména již existuje. Zvolte jiné.');
define('MF_CHANGE_TEMPLATE',			'Změnit šablonu');
define('MF_GRAV_SIZE',					'Velikost ikonky gravataru');
define('MF_GRAV_DEFAULT',				'Sndartní ikonka pro uživatele bez gravataru (Více o podpoře gravataru viz <a href="http://wakka.xiffy.nl/miniforum#gravatar">documentace</a>.)');
define('MF_TEMPLATE_DOESNT_EX',			'Šablona neexistuje.');
define('MF_REFRESH',					'Rychlost obnovy (v sekundách).');

//upgrade
define('MF_CURRENT_VERSION',			'Současná verze je:');
define('MF_UPGRADE_NOTE',				"<p>Tato verze používá nový systém šablon.".
					 "(Viz <a href='http://wakka.xiffy.nl/miniforum'>dokumentace</a>). ".
					 "Nyní je můžete je nalézt v administrační části pluginu.".
					 "Vaše šablona byla skopírována do šablony se jménem default. Pouze ". 
					 "šablona pro formulář byla nahrazena novou.</p>");
define('MF_UPGRADE_HEADING',			'NP_MiniForum upgrade');
define('MF_CHOOSE_VERSION',				'Prosím, vyberte předchozi verzi vašeho pluginu: ');
define('MF_UPGRADE_BUTTON',				'Upgrade');

//******************************************************************************
// Since 0.6.5
//******************************************************************************
//options
define('MF_CAPTCHA',				'Povolit Captcha test. (Vyžaduje nainstalovaný NP_Captcha)');
define('MF_DOCLINK',				'Dokumentace');
//admin
define('MF_FORUMLINK',				'Podpora');

//******************************************************************************
// Since 0.7.0
//******************************************************************************
//error
define('MF_UGLY_SPAM',				'SPAM! Vypadá to, že jsi spambot!');
define('MF_NOFOLLOW',				'Přidat rel="nofollow" k odkazům.');
//admin
define('MF_MISSING_POST_BODY_OR_NAME', "Musí být zadáno jméno uživatele a tělo příspěvku");
define('MF_OPTIONSLINK', 			"Nastavení");
define('MF_FORUMS', 			    "Diskuze");
define('MF_OPTIONSLINK_TITLE', 		"Nastavení pluginu");
define('MF_FORUMS_TITLE', 			"Správa diskuzí");
define('MF_DOCLINK_TITLE',			'Dokumentace - na Nucleus CMS wiki');
define('MF_FORUMLINK_TITLE',		'Vlákno MiniFora ve fóru Nucleus CMS');
define('MF_TEMPLATES_TITLE',		'Správa šablon');
define('MF_EMPTY_TEMPLATE',			'prázdná šablona');
define('MF_TEMPLATES_LIST',			'Přehled šablon');
define('MF_SAVE_FORUM_BUTTON',		'uložit fórum');
define('MF_BACK_TO_FORUMADMIN',		'zpět na správu diskusních fór');
define('MF_BACK_TO_POSTADMIN',		'zpět na přehled příspěvků');
define('MF_TEMPLATE_DELETED',		'Šablona smazána.');
define('MF_TEMPLATE_OPTIONS',		'Nastavení šablony');
define('MF_TEMPLATE_PARTS',			'Části šablony');
define('MF_FORUM_SAVED', 			'Forum uloženo.');

?>
