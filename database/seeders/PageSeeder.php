<?php

namespace Database\Seeders;

use Botble\Base\Facades\Html;
use Botble\Base\Supports\BaseSeeder;
use Botble\Page\Database\Traits\HasPageSeeder;

class PageSeeder extends BaseSeeder
{
    use HasPageSeeder;

    public function run(): void
    {
        $pages = [
            [
                'name' => 'Home',
                'content' =>
                    Html::tag('div', '[search-box title="Find your favorite homes at Flex Home" background_image="general/home-banner.jpg" enable_search_projects_on_homepage_search="yes" default_home_search_type="project"][/search-box]') .
                    Html::tag('div', '[featured-projects title="Featured projects" subtitle="We make the best choices with the hottest and most prestigious projects, please visit the details below to find out more." limit="4"][/featured-projects]') .
                    Html::tag('div', '[properties-by-locations title="Properties by locations" subtitle="Each place is a good choice, it will help you make the right decision, do not miss the opportunity to discover our wonderful properties." city="1,2,3,4,5"][/properties-by-locations]') .
                    Html::tag('div', '[properties-for-sale title="Properties For Sale" subtitle="Below is a list of properties that are currently up for sale" limit="8"][/properties-for-sale]') .
                    Html::tag('div', '[properties-for-rent title="Properties For Rent" subtitle="Below is a detailed price list of each property for rent" limit="8"][/properties-for-rent]') .
                    Html::tag('div', '[featured-agents title="Featured Agents"][/featured-agents]') .
                    Html::tag(
                        'div',
                        '[recently-viewed-properties title="Recently Viewed Properties" subtitle="Your currently viewed properties." limit="8"][/recently-viewed-properties]'
                    ) .
                    Html::tag('div', '[latest-news title="News" subtitle="Below is the latest real estate news we get regularly updated from reliable sources." limit="4"][/latest-news]')
                ,
                'template' => 'homepage',
            ],
            [
                'name' => 'News',
                'content' => '---',
                'template' => 'default',
            ],
            [
                'name' => 'About us',
                'description' => 'Founded on August 28, 1993 (formerly known as Truong Thinh Phat Construction Co., Ltd.), Flex Home operates in the field of real estate business, building villas for rent.
With the slogan "Breaking time, through space" with a sustainable development strategy, taking Real Estate as a focus area, Flex Home is constantly connecting between buyers and sellers in the field.',
                'content' => '<h4><span style="font-size:18px;"><b>1. COMPANY</b><span style="font-family:Arial,Helvetica,sans-serif;"><strong> PROFILE</strong></span></span></h4>

<p><span style="font-size:16px;"><span style="font-family:Arial,Helvetica,sans-serif;">Founded on August 28, 1993 (formerly known as Truong Thinh Phat Construction Co., Ltd.), Flex Home operates in the field of real estate business, building villas for rent.<br />
With the slogan &quot;Breaking time, through space&quot; with a sustainable development strategy, taking Real Estate as a focus area, Flex Home is constantly connecting between buyers and sellers in the field. Real estate, bringing people closer together, over the distance of time and space, is a reliable place for real estate investment - an area that is constantly evolving over time.</span></span></p>

<blockquote>
<h2 style="font-style: italic; text-align: center;"><span style="font-size:24px;"><strong><span style="font-family:Arial,Helvetica,sans-serif;"><span style="color:#16a085;">&quot;Breaking time, through space&quot;</span></span></strong></span></h2>
</blockquote>

<h4 style="text-align: center;"><img alt="" src="' . url('') . '/storage/general/asset-3-at-3x.png" style="width: 90%;" /></h4>

<h4><span style="font-size:18px;"><b><font face="Arial, Helvetica, sans-serif">2. VISION&nbsp;</font></b></span></h4>

<p><span style="font-size:16px;"><span style="font-family:Arial,Helvetica,sans-serif;">- Acquiring domestic areas.<br />
- Reaching far across continents.</span></span></p>

<h4><span style="font-size:18px;"><b>3. MISSION</b></span></h4>

<p><span style="font-size:16px;"><span style="font-family:Arial,Helvetica,sans-serif;">- Creating the community<br />
- Building destinations<br />
- Nurture happiness</span></span></p>

<p><img alt="" src="' . url('') . '/storage/general/vietnam-office-4.jpg" /></p>
',
                'template' => 'default',
            ],
            [
                'name' => 'Contact',
                'content' => '<p>[contact-form][/contact-form]<br />
&nbsp;</p>

<h3>Directions</h3>

<p>[google-map]North Link Building, 10 Admiralty Street, 757695 Singapore[/google-map]</p>

<p>&nbsp;</p>',
                'template' => 'default',
            ],
            [
                'name' => 'Terms & Conditions',
                'description' => 'Copyrights and other intellectual property rights to all text, images, audio, software and other content on this site are owned by Flex Home and its affiliates. Users are allowed to view the contents of the website, cite the contents by printing, downloading the hard disk and distributing it to others for non-commercial purposes.',
                'content' => '<p style="text-align: justify;"><span style="font-size:16px;"><span style="font-family:Arial,Helvetica,sans-serif;">Access to and use of the Flex Home website is subject to the following terms, conditions, and relevant laws of Vietnam.</span></span></p>

<h4 style="text-align: justify;"><span style="font-size:18px;"><span style="font-family:Arial,Helvetica,sans-serif;"><strong>1. Copyright</strong></span></span></h4>

<p style="text-align: justify;"><span style="font-size:16px;"><span style="font-family:Arial,Helvetica,sans-serif;">Copyrights and other intellectual property rights to all text, images, audio, software and other content on this site are owned by Flex Home and its affiliates. Users are allowed to view the contents of the website, cite the contents by printing, downloading the hard disk and distributing it to others for non-commercial purposes, providing information or personal purposes. </span></span><span style="font-size:16px;"><span style="font-family:Arial,Helvetica,sans-serif;">Any content from this site may not be used for sale or distribution for profit, nor may it be edited or included in any other publication or website.</span></span></p>

<h4 style="text-align: justify;"><span style="font-size:18px;"><span style="font-family:Arial,Helvetica,sans-serif;"><strong>2. Content</strong></span></span></h4>

<p style="text-align: justify;"><span style="font-size:16px;"><span style="font-family:Arial,Helvetica,sans-serif;">The information on this website is compiled with great confidence but for general information research purposes only. While we endeavor to maintain updated and accurate information, we make no representations or warranties in any manner regarding completeness, accuracy, reliability, appropriateness or availability in relation to web site, or related information, product, service, or image within the website for any purpose. </span></span></p>

<p style="text-align: justify;"><span style="font-size:16px;"><span style="font-family:Arial,Helvetica,sans-serif;">Flex Home and its employees, managers, and agents are not responsible for any loss, damage or expense incurred as a result of accessing and using this website and the sites. </span></span><span style="font-size:16px;"><span style="font-family:Arial,Helvetica,sans-serif;">The web is connected to it, including but not limited to, loss of profits, direct or indirect losses. We are also not responsible, or jointly responsible, if the site is temporarily inaccessible due to technical issues beyond our control. Any comments, suggestions, images, ideas and other information or materials that users submit to us through this site will become our exclusive property, including the right to may arise in the future associated with us.</span></span></p>

<p style="text-align: center;"><span style="font-size:16px;"><span style="font-family:Arial,Helvetica,sans-serif;"><img alt="" src="' . url('') . '/storage/general/copyright.jpg" style="width: 90%;" /></span></span></p>

<h4 style="text-align: justify;"><span style="font-size:18px;"><span style="font-family:Arial,Helvetica,sans-serif;"><strong>3. Note on&nbsp;connected sites</strong></span></span></h4>

<p style="text-align: justify;"><span style="font-size:16px;"><span style="font-family:Arial,Helvetica,sans-serif;">At many points in the website, users can get links to other websites related to a specific aspect. This does not mean that we are related to the websites or companies that own these websites. Although we intend to connect users to sites of interest, we are not responsible or jointly responsible for our employees, managers, or representatives. with other websites and information contained therein.</span></span></p>
',
                'template' => 'default',
            ],
            [
                'name' => 'Cookie Policy',
                'content' => Html::tag('h3', 'EU Cookie Consent') .
                    Html::tag(
                        'p',
                        'To use this website we are using Cookies and collecting some Data. To be compliant with the EU GDPR we give you to choose if you allow us to use certain Cookies and to collect some Data.'
                    ) .
                    Html::tag('h4', 'Essential Data') .
                    Html::tag(
                        'p',
                        'The Essential Data is needed to run the Site you are visiting technically. You can not deactivate them.'
                    ) .
                    Html::tag(
                        'p',
                        '- Session Cookie: PHP uses a Cookie to identify user sessions. Without this Cookie the Website is not working.'
                    ) .
                    Html::tag(
                        'p',
                        '- XSRF-Token Cookie: Laravel automatically generates a CSRF "token" for each active user session managed by the application. This token is used to verify that the authenticated user is the one actually making the requests to the application.'
                    ),
                'template' => 'default',
            ],
            [
                'name' => 'Properties',
                'content' =>
                    Html::tag('div', '[properties-list title="Discover our properties" description="Discover our properties" description="Each place is a good choice, it will help you make the right decision, do not miss the opportunity to discover our wonderful properties." number_of_properties_per_page="12"][/properties-list]')
                ,
                'template' => 'homepage',
            ],
            [
                'name' => 'Projects',
                'content' =>
                    Html::tag('div', '[projects-list  title="Discover our projects" description="We make the best choices with the hottest and most prestigious projects, please visit the details below to find out more" number_of_projects_per_page="12"][/projects-list]')
                ,
                'template' => 'homepage',
            ],
        ];

        $this->createPages($pages);
    }
}
