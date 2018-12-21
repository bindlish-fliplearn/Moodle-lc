#filter_viewerjs

This is a Moodle filter that converts links to local PDFs, ODPs, ODTs, and ODSs into an embedded player powered by
[ViewerJS](http://viewerjs.org). It uses the model from [moodle-filter\_jwplayer](https://github.com/lucisgit/moodle-filter_jwplayer) of
creating a Moodle `core_media_player` renderer and using that to change links into an embedded player.

This plugin has only been tested on Moodle 2.8.

##Installation

Note that [ViewerJS](http://viewerjs.org) is licensed AGPLv3. To ease the licensing and distribution requirements of this filter, the
ViewerJS library must be installed separately, instead of it being bundled with the filter.

1. Place the files for this filter at `/filter/viewerjs` in your Moodle Installation
2. Download [ViewerJS](http://viewerjs.org/getit/) and extract the `viewerjs-*/ViewerJS/` to `/filter/viewerjs/lib/viewerjs/` in your
   Moodle installation. If successful, `/filter/viewerjs/lib/viewerjs` should contain files such as `index.html`.
3. Create a text file named `thirdpartylibs.xml` with the following content

```
<?xml version="1.0"?>
	<libraries>
		<library>
			<location>lib/viewerjs</location>
			<name>ViewerJS</name>
			<version>VERSION_NUMBER</version>
			<license>AGPL</license>
			<licenseversion>3.0</licenseversion>
		</library>
	</libraries>
````

 replacing `VERSION_NUMBER` with the version of ViewerJS that was downloaded
4. Using a web browser, go to `/admin` to complete installation

##Usage

1. Create a page activity in a course
2. Add some text
3. Create a link to a PDF
4. When you save and view the page, you should see the PDF displayed in the embedded player

##Screenshots
![Screenshot of a link to a PDF being displayed using the ViewerJS player](/screenshot.png?raw=true "Screenshot")
