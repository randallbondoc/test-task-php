<?php

use Illuminate\Database\Seeder;

class MailChimpListsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('mail_chimp_lists')->delete();
        
        \DB::table('mail_chimp_lists')->insert(array (
            0 => 
            array (
                'campaign_defaults' => 'a:4:{s:9:"from_name";s:7:"Randall";s:10:"from_email";s:13:"rb1@gmail.com";s:7:"subject";s:4:"test";s:8:"language";s:3:"eng";}',
                'contact' => 'a:8:{s:7:"company";s:5:"flexi";s:8:"address1";s:5:"lagro";s:8:"address2";s:0:"";s:4:"city";s:2:"qc";s:5:"state";s:3:"mnl";s:3:"zip";s:4:"1100";s:7:"country";s:2:"PH";s:5:"phone";s:4:"0929";}',
                'email_type_option' => '1',
                'id' => '6e6dd766-f1ce-11ea-9e92-1831bf96e34c',
                'mail_chimp_id' => 'cb7d4a5d18',
                'name' => 'Randall',
                'notify_on_subscribe' => '',
                'notify_on_unsubscribe' => '',
                'permission_reminder' => '',
                'use_archive_bar' => '',
                'visibility' => '',
            ),
        ));
        
        
    }
}