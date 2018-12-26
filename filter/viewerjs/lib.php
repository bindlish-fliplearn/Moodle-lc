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
 * Media player that generates the HTML embed code to embed support file types
 *
 * Only local PDFs, ODTs, ODPs, ODSs are supported.
 *
 * @package    filter_viewerjs
 * @copyright  2015 Abir Viqar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/medialib.php');

class filter_viewerjs_media_player extends core_media_player {

    /**
     * Generates code required to embed the player.
     *
     * The url contained in $urls must be a local file.
     *
     * Unlike other core_media_players, the $urls array should only contain
     * a single url and the $options array is ignored.
     *
     * On failure, the function returns an empty string
     *
     * @see core_media_player::embed
     * @param array $urls URL of media file
     * @param string $name Display name; '' to use default
     * @param int $width Optional width; 0 to use default
     * @param int $height Optional height; 0 to use default
     * @param array $options Options array
     * @return string HTML code for embed
     */
    public function embed($urls, $name = null, $width = false, $height = false, $options = false) {
        global $CFG;
        // don't expect alternative urls
        if (count($urls) !== 1) {
            return '';
        }

        $file_url            = new moodle_url($urls[0]);
        $viewerjs_player_url = new moodle_url('/filter/viewerjs/lib/viewerjs');
        // we assume the filter/viewerjs/lib/viewerjs directory will be four directories away from the initial public directory

        preg_match_all('/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $urls[0], $res_arr);
        $link = '';
        if (!empty($res_arr)) {
            $link = $res_arr['href'][0];
        }
        $localurl = substr($link, strlen($CFG->wwwroot));
        // $viewerjs_player_url->set_anchor('../../../..' . $file_url->out_as_local_url());
        $viewerjs_player_url->set_anchor('../../../../' . $localurl);

        if (!$width) {
            $width = '100%';
        }
        if (!$height) {
            $height = 500;
        }

        $output = html_writer::tag('iframe', '', array(
            'src'                   => $viewerjs_player_url->out(),
            'width'                 => $width,
            'height'                => $height,
            'webkitallowfullscreen' => 'webkitallowfullscreen',
            'mozallowfullscreen'    => 'mozallowfullscreen',
            'allowfullscreen'       => 'allowfullscreen'
        ));

        return $output;
    }

    public function get_supported_extensions() {
        return array(
            'pdf',
            'ods',
            'odp',
            'odt'
        );
    }

    /**
     * Given a list of URLs, returns a reduced array containing only those URLs
     * which are supported by this player.
     *
     * This media player only supports local urls.
     *
     * @param array $urls Array of moodle_url
     * @param array $options Options (same as will be passed to embed)
     * @return array Array of supported moodle_url
     */
    public function list_supported_urls(array $urls, array $options = array()) {
        $extensions = $this->get_supported_extensions();
        $result = array();
        foreach ($urls as $url) {
            try {
                $url->out_as_local_url();
            }
            catch (coding_exception $e) {
                continue;
            }
            if (in_array(core_media::get_extension($url), $extensions)) {
                $result[] = $url;
            }
        }
        return $result;
    }

    public function get_rank() {
        return 0;
    }

    public function is_enabled() {
        return true;
    }
}
