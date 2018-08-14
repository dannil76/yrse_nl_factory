# Yves Rocher Newsletter Helper Scripts

## Setup

1. Clone the repository to your local computer. (ex. **_~/yrse_nl_factory/_**)

2. Open terminal and type `cd ~/yrse_nl_factory/app`

3. Run `composer install` (You need **composer** to be installed. Read more at https://getcomposer.org/download/)


#### Minimum requirement

php 7.1+ (with yaml extension)

composer

libyaml

curl


## How to code newsletters

#### Prepare campaign

1. Update template images (menu1.jpg, menu2.jpg, menu3.jpg) as needed found in **_/newsletter/templates/nl_0/[se,dk,no,fi]/images/template/_**

2. Create a new directory in **_/newsletter/src/_** and name it accordingly to the current campaign month. (ex. 1_jan)

3. Copy twig template file **_/newsletter/templates/campaign_YYMM_MONTH.twig_** to **_/newsletter/src/[1_jan]/_**

   Rename it accordingly to the current campaign month. (ex. campaign_1801_januari.twig)

4. Open it and update campaign expire date


#### Generate newsletter

1. Duplicate **_/newsletter/templates/nl_0_** to **_newsletter/src/[1_jan]_** and rename it (ex. nl_1, etc.)

2. Translate PSD

3. Slice PSD

4. Move sliced images (ie **_/psd_slices/\_current/[se,no,dk,fi]_** to the newly created nl_x folder in **_/newsletter/src/[1_jan]/_**

5. Edit **_/newsletter/src/nl_x/lang_specific.twig_**

   5.1 Enter **subject line**

   5.2 Enter **preheader text**

   5.3 Edit code

   5.4 Save it!


6. Generate newsletter html. In terminal type:

   `cd ~/yrse_nl_factory/app/bin`

   `./generate_newsletter.php 1_jan`


7. Proof newsletters found in **_/newsletter/dist/_**

8. Upload images to FTP server.

   At the command prompt type: (terminal)

   `cd ~/yrse_nl_factory/app/bin` You should be here already. (:

   `./ftp_images.php`


9. Prepare html for Harmony.

   `./premail_newsletter.php`


10. Proof html found in **_/Harmony/html/_**


#### Create, proof, approve and deploy/schedule message in Harmony

1. Duplicate **_/Harmony/recipe/\_\_nl\_0.yaml_** and rename it (ex. nl_1.yaml)

2. Open and update **_nl\_1.yaml_** as needed

3. Create/push message

   In the terminal run

   `./1_create_message.php`


4. Send proof 

   In the terminal run

   `./2_proof_message.php`


5. Proof ok? Approve message

   In the terminal run

   `./3_approve_message.php`
 

6. Schedule message

   In the terminal run

   `./4_schedule_message.php`
 

DONE!