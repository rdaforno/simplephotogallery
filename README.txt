Simple photo gallery script v1.4
(c) 2019 Reto Da Forno

Usage:
- adjust the settings in config.php
- create a new folder 'photo' and copy all photos you want to include in the gallery into that folder (jpegs only)
- upload the files to a webserver

Note: You should resize your photos before uploading them to avoid long loading times.
      Recommended photo width: <= 2000px, jpeg compression: 80-90%

Remark: This script uses photoswipe (visit photoswipe.com for more info and updates).

How it works:
Upon the first access to the index page, a photo database (text file) and thumbnails will
be generated. To recreate the database, simply delete the files 'itemlist' and 
'thumblist'. To recreate the thumbnails, delete the folder 'thumbs'.
This script will also generate files for simple statistics (visitor and download counter).
You can enable password protected access to the gallery by setting a password in the config file. Note that the photo folder as well as all generated files will be renamed to make guessing the filenames virtually impossible. However, for the password protection to be effective, directory listings must be disabled on the server and an https connection should be used.
