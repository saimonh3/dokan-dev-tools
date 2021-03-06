<?php

namespace Dokan\DevTools\CLI;

use Dokan\DevTools\Faker;
use WP_User_Query;

/**
* Dokan Vendor CLI Commands
*
* @since 1.0.0
*/
class Vendor extends CLI {

    protected $base_command = 'dokan vendor';

    /**
    * Class constructor
    *
    * @since 1.0.0
    */
    public function __construct() {
        $this->add_command( 'generate', 'generate' );
        $this->add_command( 'delete', 'delete' );
    }

    /**
     * Generate Dokan Vendors
     *
     * ## OPTIONS
     *
     * [--count=<no_of_vendors>]
     * : No of vendors. Default is 10.
     *
     * ## EXAMPLES
     *
     *     # Generate 10 vendors
     *     $ wp dokan vendor generate
     *
     *     # Generate 20 vendors
     *     $ wp dokan vendor generate --count=20
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function generate( $args, $assoc_args ) {
        $faker = Faker::get();

        $count = 10;
        $generated = 0;

        if ( ! empty( $assoc_args['count'] ) ) {
            $count = $assoc_args['count'];
        }

        $message = sprintf( 'Generating %d vendors', $count );

        $progress = $this->make_progress_bar( $message, $count );

        add_filter( 'woocommerce_email_enabled_dokan_new_seller', '__return_false' );

        for ( $i = 0; $i < $count; $i++ ) {
            $email = $faker->safeEmail;
            $shopname = $faker->unique()->userName;

            $userdata = [
                'user_login'    => $email,
                'user_email'    => $email,
                'user_pass'     => ',lpmkonji',
                'first_name'    => $faker->firstName,
                'last_name'     => $faker->lastName,
                'role'          => 'seller',
                'user_nicename' => $shopname,
            ];

            $vendor = wp_insert_user( $userdata );

            if ( ! is_wp_error( $vendor ) ) {
                $dokan_settings = array(
                    'store_name'     => $shopname,
                    'social'         => array(),
                    'payment'        => array(),
                    'phone'          => $faker->phoneNumber,
                    'show_email'     => 'no',
                    'dokan_category' => '',
                    'banner'         => 0,
                );

                // 50-50 chance to add location data
                $add_location_data = $faker->boolean();

                if ( $add_location_data ) {
                    $lat     = $faker->latitude( 23.70, 23.90 );
                    $long    = $faker->longitude( 90.25, 90.50 );

                    $dokan_settings['find_address'] = $faker->address;
                    $dokan_settings['location'] = $lat . ',' . $long;
                }

                update_user_meta( $vendor, 'dokan_profile_settings', $dokan_settings );
                update_user_meta( $vendor, 'dokan_store_name', $dokan_settings['store_name'] );

                do_action( 'dokan_new_seller_created', $vendor, $dokan_settings );

                do_action( 'dokan_dev_cli_vendor_generated', $vendor );

                ++$generated;
            }

            $progress->tick();
        }

        $progress->finish();

        $message = sprintf( 'Generated %d vendors', $generated );

        $this->success( $message );
    }

    /**
     * Delete Dokan Vendors
     *
     * ## EXAMPLES
     *
     *     # Delete all vendors
     *     $ wp dokan vendor delete
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function delete( $args, $assoc_args ) {
        $args = [
            'role' => 'seller'
        ];

        $vendors = new WP_User_Query( $args );

        if ( ! $vendors->get_total() ) {
            $this->error( 'No vendor found' );

        } else {
            $count = $vendors->get_total();
            $deleted = 0;

            $message  = sprintf( 'Deleting %d vendors', $count );
            $progress = $this->make_progress_bar( $message, $count );

            $results = $vendors->get_results();

            foreach ( $results as $vendor ) {
                if ( wp_delete_user( $vendor->ID ) ) {
                    ++$deleted;
                }

                $progress->tick();
            }

            $progress->finish();

            $message = sprintf( 'Deleted %d vendors', $deleted );

            $this->success( $message );
        }
    }
}
