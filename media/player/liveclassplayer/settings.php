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
 * @package    media_liveclassplayer
 * @copyright  2017 Ruslan Kabalin, Lancaster University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    require_once(__DIR__ . '/lib.php');
    require_once(__DIR__ . '/adminlib.php');
    require_once(__DIR__ . '/classes/plugin.php');
    $liveclassplayer = new media_liveclassplayer_plugin();

    // Hosting method.
    $hostingmethodchoice = array(
        'cloud' => get_string('hostingmethodcloud', 'media_liveclassplayer'),
        'self' => get_string('hostingmethodself', 'media_liveclassplayer'),
    );
    $settings->add(new media_liveclassplayer_hostingmethod_setting('media_liveclassplayer/hostingmethod',
            get_string('hostingmethod', 'media_liveclassplayer'),
            get_string('hostingmethoddesc', 'media_liveclassplayer', MEDIA_JWPLAYER_CLOUD_VERSION),
            'cloud', $hostingmethodchoice));

    // License key.
    $settings->add(new media_liveclassplayer_license_setting('media_liveclassplayer/licensekey',
            get_string('licensekey', 'media_liveclassplayer'),
            get_string('licensekeydesc', 'media_liveclassplayer'),
            ''));

    // Enabled extensions.
    $supportedextensions = $liveclassplayer->list_supported_extensions();
    $enabledextensionsmenu = array_combine($supportedextensions, $supportedextensions);
    array_splice($supportedextensions, array_search('mpd', $supportedextensions), 1);  // disable mpeg-dash as it requires premium licence or higher.
    array_splice($supportedextensions, array_search('m3u8', $supportedextensions), 1);  // disable HLS by default as it needs a Premium licence
    $settings->add(new admin_setting_configmultiselect('media_liveclassplayer/enabledextensions',
            get_string('enabledextensions', 'media_liveclassplayer'),
            get_string('enabledextensionsdesc', 'media_liveclassplayer'),
            $supportedextensions, $enabledextensionsmenu));

    // RTMP support.
    $settings->add(new admin_setting_configcheckbox('media_liveclassplayer/supportrtmp',
            get_string('supportrtmp', 'media_liveclassplayer'),
            get_string('supportrtmpdesc', 'media_liveclassplayer'),
            0));

    // Enabled events to log.
    $supportedevents = $liveclassplayer->list_supported_events();
    $supportedeventsmenu = array_combine($supportedevents, $supportedevents);
    $settings->add(new admin_setting_configmultiselect('media_liveclassplayer/enabledevents',
            get_string('enabledevents', 'media_liveclassplayer'),
            get_string('enabledeventsdesc', 'media_liveclassplayer'),
            array('play', 'pause', 'complete'), $supportedeventsmenu));

    // Appearance related settings.
    $settings->add(new admin_setting_heading('appearanceconfig',
            get_string('appearanceconfig', 'media_liveclassplayer'), ''));

    // Default Poster Image.
    $settings->add(new admin_setting_configstoredfile('media_liveclassplayer/defaultposter',
            get_string('defaultposter', 'media_liveclassplayer'),
            get_string('defaultposterdesc', 'media_liveclassplayer'),
            'defaultposter', 0, array('maxfiles' => 1, 'accepted_types' => array('.jpg', '.png'))));

    // Download button.
    $settings->add(new admin_setting_configcheckbox('media_liveclassplayer/downloadbutton',
            get_string('downloadbutton', 'media_liveclassplayer'),
            get_string('downloadbuttondesc', 'media_liveclassplayer'),
            0));

    // Display Style (Fixed Width or Responsive).
    $displaystylechoice = array(
        'fixed' => get_string('displayfixed', 'media_liveclassplayer'),
        'responsive' => get_string('displayresponsive', 'media_liveclassplayer'),
    );
    $settings->add(new admin_setting_configselect('media_liveclassplayer/displaystyle',
            get_string('displaystyle', 'media_liveclassplayer'),
            get_string('displaystyledesc', 'media_liveclassplayer'),
            'fixed', $displaystylechoice));

    // Skins.
    $skins = array('beelden', 'bekle', 'five', 'glow', 'roundster', 'six', 'stormtrooper', 'vapor');
    $skinoptions = array('' => get_string('standardskin', 'media_liveclassplayer'));
    $skinoptions = array_merge($skinoptions, array_combine($skins, $skins));
    $settings->add(new admin_setting_configselect('media_liveclassplayer/skin',
            get_string('useplayerskin', 'media_liveclassplayer'), '', '', $skinoptions));

    // Custom skin.
    $settings->add(new admin_setting_configtext('media_liveclassplayer/customskincss',
            get_string('customskincss', 'media_liveclassplayer'),
            get_string('customskincssdesc', 'media_liveclassplayer'),
            ''));

    // Allow empty title.
    $settings->add(new admin_setting_configcheckbox('media_liveclassplayer/emptytitle',
            get_string('emptytitle', 'media_liveclassplayer'),
            get_string('emptytitledesc', 'media_liveclassplayer'),
            0));

    // Google Analytics settings.
    $settings->add(new admin_setting_heading('googleanalyticsconfig',
            get_string('googleanalyticsconfig', 'media_liveclassplayer'),
            get_string('googleanalyticsconfigdesc', 'media_liveclassplayer')));

    $addhtml = new moodle_url('/admin/settings.php', array('section' => 'additionalhtml'));
    $settings->add(new admin_setting_configcheckbox('media_liveclassplayer/googleanalytics',
            get_string('googleanalytics', 'media_liveclassplayer'),
            get_string('googleanalyticsdesc', 'media_liveclassplayer', $addhtml->out()),
            0));

    $settings->add(new admin_setting_configtext('media_liveclassplayer/gaidstring',
            get_string('gaidstring', 'media_liveclassplayer'),
            get_string('gaidstringdesc', 'media_liveclassplayer'),
            'file'));

    $settings->add(new admin_setting_configtext('media_liveclassplayer/galabel',
            get_string('galabel', 'media_liveclassplayer'),
            get_string('galabeldesc', 'media_liveclassplayer'),
            'file'));
}
