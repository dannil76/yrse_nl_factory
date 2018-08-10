# Yves Rocher Newsletter Helper Scripts

## Setup

`cd app`

Run `composer install`


#### Minimum requirement

composer

php 7.1+ (with extension yaml)

libyaml

curl


### How to code newsletters

#### Prepare new campaign

1. Update template images (menu1.jpg, menu2.jpg, menu3.jpg) as needed found in _/newsletter/templates/nl_0/[se,dk,no,fi]/images/template/_

2. Create a new directory in _/newsletter/src/_ and name it accordingly to the current campaign month. (ex. 1_jan)

3. Copy twig template file _/newsletter/templates/campaign_YYMM_MONTH.twig_ to _/newsletter/src/[1_jan]/_
   Rename it accordingly to the current campaign month. (ex. campaign_1801_januari.twig)


#### Generate newsletter

1. Copy _/newsletter/templates/nl_0_ to _newsletter/src/[1_jan]_ and name it (ex. nl_1, etc.)

2. Translate PSD

3. Slice PSD

4. Move sliced images (ie _/psd_slices/[se,no,dk,fi]_ to the newly created nl_x folder in _newsletter/src/[1_jan]/_

5. Edit _/newsletter/src/nl_x/lang_specific.twig_

6. Run _/app/generate_newsletter.php_ by starting terminal.app (macOS) and type:

   `cd ~/yrse_nl_factory/app/bin`

   `./generate_newsletter.php 1_jan`


7. Proof newsletters found in _/newsletter/dist/_

8. Upload images to FTP server.

   At the command prompt type: (Terminal)

   `cd ~/yrse_nl_factory/app/bin` You should be at this location already. (:

   `./ftp_images.php`


9. Prepare html.

   `./premail_newsletter.php`


10. Proof html found in _/Harmony/html/_


#### Push newsletter to Harmony

TODO...