Simple photo gallery script v1.4
(c) 2019 Reto Da Forno

Usage:
- adjust the settings in config.php
- create a new folder 'photo' and copy all photos you want to include in the gallery into that folder (jpegs only)
- upload the files to a webserver

Note: You should resize your photos before uploading them to avoid long loading times.
      Recommended photo width: <= 2000px, jpeg compression: 80-90%

Upon the first access to this page, a photo database (text files) and thumbnails will
be generated. To recreate the database, simply delete the files 'itemlist' and 
'thumblist'. To recreate the thumbnails, delete the folder 'thumbs'.
This script will also generate several files for simple statistics:
- 'counter' (visitor count)
- 'dlcounter' (download counter for the zip file with all photos)
- 'ipaddr' (IP address and access time log of all visitors, disabled by default)

Remark: This script uses photoswipe (visit photoswipe.com for more info and updates).