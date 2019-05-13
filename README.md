# Simple photo gallery script

This is a simple script that generates a photo gallery for your website. The script extracts infos from the EXIF data and utilizes photoswipe to display the photos.  
Only minimal configuration is required, which enables you to set up a new gallery within seconds.  

(c) 2019 Reto Da Forno

## Requirements
No separate database is required, all you need is a php enabled webhost.

## Usage
- adjust the settings in `config.php`  
- create a new folder `photo` and copy all photos you want to include in the gallery into that folder (jpegs only)  
- upload the files to a webserver  

Note: You should resize your photos before uploading them to avoid long loading times (recommended photo width: <= 2000px, jpeg compression: 80-90%).

## How it works
Upon the first access to the index page, a photo database (text file) and thumbnails will be generated. The script will read out EXIF data from the photos: title, camera, shutter speed, f-stop and ISO. To recreate the database, simply delete the files `itemlist` and `thumblist`. To recreate the thumbnails, delete the folder `thumbs`.
In addition, some basic stats (visitors counter) will be recorded.  
This script utilizes photoswipe, visit photoswipe.com for more info.

## Features

### Display style
The following configuration options are available to change the appearance of the thumbnail list:
- `$thumbsize`: thumbnail size in pixel (short edge)
- `$withspacing`: when set to false, thumbnails will be aligned one after the other without any spacing or border
- `$squarethumbs`: when set to true, thumbs will be displayed as squares rather than rectangles

### Download photos
There is a feature that lets users download the whole gallery as a zip file. To disable this option, leave the configuration parameter `$download_filename` blank.

### Password protection
You can enable password protected access to the gallery by setting a password in the config file (parameter `$password`). Note that the photo folder as well as all generated files will be renamed to make guessing the filenames virtually impossible. However, for the password protection to be effective, directory listings must be disabled on the server and an https connection should be used.

### Filename randomization
If you want to assign random filenames to the photos, you can enable the option `$randomize_filenames`. This will rename all the files in `photo`. The resulting new filename is the sha1 hash of the original file name. Note that you will need to recreate all thumbnails by deleting the `thumbs` folder.
