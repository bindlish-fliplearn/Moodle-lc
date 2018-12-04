<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  JW Player media plugin settings.
 *
 * @package    media_primeplayer
 * @copyright  2017 Ruslan Kabalin, Lancaster University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    require_once(__DIR__ . '/lib.php');
    require_once(__DIR__ . '/adminlib.php');
    require_once(__DIR__ . '/classes/plugin.php');
    $primeplayer = new media_primeplayer_plugin();

    // Hosting method.
    $hostingmethodchoice = array(
        'cloud' => get_string('hostingmethodcloud', 'media_primeplayer'),
        'self' => get_string('hostingmethodself', 'media_primeplayer'),
    );
    $settings->add(new media_primeplayer_hostingmethod_setting('media_primeplayer/hostingmethod',
            get_string('hostingmethod', 'media_primeplayer'),
            get_string('hostingmethoddesc', 'media_primeplayer', MEDIA_JWPLAYER_CLOUD_VERSION),
            'cloud', $hostingmethodchoice));

    // License key.
    $settings->add(new media_primeplayer_license_setting('media_primeplayer/licensekey',
            get_string('licensekey', 'media_primeplayer'),
            get_string('licensekeydesc', 'media_primeplayer'),
            ''));

    // Enabled extensions.
    $supportedextensions = $primeplayer->list_supported_extensions();
    $enabledextensionsmenu = array_combine($supportedextensions, $supportedextensions);
    array_splice($supportedextensions, array_search('mpd', $supportedextensions), 1);  // disable mpeg-dash as it requires premium licence or higher.
    array_splice($supportedextensions, array_search('m3u8', $supportedextensions), 1);  // disable HLS by default as it needs a Premium licence
    $settings->add(new admin_setting_configmultiselect('media_primeplayer/enabledextensions',
            get_string('enabledextensions', 'media_primeplayer'),
            get_string('enabledextensionsdesc', 'media_primeplayer'),
            $supportedextensions, $enabledextensionsmenu));

    // RTMP support.
    $settings->add(new admin_setting_configcheckbox('media_primeplayer/supportrtmp',
            get_string('supportrtmp', 'media_primeplayer'),
            get_string('supportrtmpdesc', 'media_primeplayer'),
            0));

    // Enabled events to log.
    $supportedevents = $primeplayer->list_supported_events();
    $supportedeventsmenu = array_combine($supportedevents, $supportedevents);
    $settings->add(new admin_setting_configmultiselect('media_primeplayer/enabledevents',
            get_string('enabledevents', 'media_primeplayer'),
            get_string('enabledeventsdesc', 'media_primeplayer'),
            array('play', 'pause', 'complete'), $supportedeventsmenu));

    // Appearance related settings.
    $settings->add(new admin_setting_heading('appearanceconfig',
            get_string('appearanceconfig', 'media_primeplayer'), ''));

    // Default Poster Image.
    $settings->add(new admin_setting_configstoredfile('media_primeplayer/defaultposter',
            get_string('defaultposter', 'media_primeplayer'),
            get_string('defaultposterdesc', 'media_primeplayer'),
            'defaultposter', 0, array('maxfiles' => 1, 'accepted_types' => array('.jpg', '.png'))));

    // Download button.
    $settings->add(new admin_setting_configcheckbox('media_primeplayer/downloadbutton',
            get_string('downloadbutton', 'media_primeplayer'),
            get_string('downloadbuttondesc', 'media_primeplayer'),
            0));

    // Display Style (Fixed Width or Responsive).
    $displaystylechoice = array(
        'fixed' => get_string('displayfixed', 'media_primeplayer'),
        'responsive' => get_string('displayresponsive', 'media_primeplayer'),
    );
    $settings->add(new admin_setting_configselect('media_primeplayer/displaystyle',
            get_string('displaystyle', 'media_primeplayer'),
            get_string('displaystyledesc', 'media_primeplayer'),
            'fixed', $displaystylechoice));

    // Skins.
    $skins = array('beelden', 'bekle', 'five', 'glow', 'roundster', 'six', 'stormtrooper', 'vapor');
    $skinoptions = array('' => get_string('standardskin', 'media_primeplayer'));
    $skinoptions = array_merge($skinoptions, array_combine($skins, $skins));
    $settings->add(new admin_setting_configselect('media_primeplayer/skin',
            get_string('useplayerskin', 'media_primeplayer'), '', '', $skinoptions));

    // Custom skin.
    $settings->add(new admin_setting_configtext('media_primeplayer/customskincss',
            get_string('customskincss', 'media_primeplayer'),
            get_string('customskincssdesc', 'media_primeplayer'),
            ''));

    // Allow empty title.
    $settings->add(new admin_setting_configcheckbox('media_primeplayer/emptytitle',
            get_string('emptytitle', 'media_primeplayer'),
            get_string('emptytitledesc', 'media_primeplayer'),
            0));

    // Google Analytics settings.
    $settings->add(new admin_setting_heading('googleanalyticsconfig',
            get_string('googleanalyticsconfig', 'media_primeplayer'),
            get_string('googleanalyticsconfigdesc', 'media_primeplayer')));

    $addhtml = new moodle_url('/admin/settings.php', array('section' => 'additionalhtml'));
    $settings->add(new admin_setting_configcheckbox('media_primeplayer/googleanalytics',
            get_string('googleanalytics', 'media_primeplayer'),
            get_string('googleanalyticsdesc', 'media_primeplayer', $addhtml->out()),
            0));

    $settings->add(new admin_setting_configtext('media_primeplayer/gaidstring',
            get_string('gaidstring', 'media_primeplayer'),
            get_string('gaidstringdesc', 'media_primeplayer'),
            'file'));

    $settings->add(new admin_setting_configtext('media_primeplayer/galabel',
            get_string('galabel', 'media_primeplayer'),
            get_string('galabeldesc', 'media_primeplayer'),
            'file'));
}
