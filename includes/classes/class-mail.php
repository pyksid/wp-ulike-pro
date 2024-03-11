<?php
/**
 * Mail
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

class WP_Ulike_Pro_Mail {

    protected $convert_tags;

    /**
     * Mail constructor.
     */
    function __construct() {
        //mandrill compatibility
        add_filter( 'mandrill_nl2br', array( &$this, 'mandrill_nl2br' ) );
    }

    function prepare_tags( $args ){
        $extra_vars = apply_filters( 'wp_ulike_pro_mail_extra_vars', array(
            '{password_reset_link}' => ! empty( $args['user_id'] ) ? WP_Ulike_Pro_Permalinks::reset_url( $args['user_id'] ) : '#'
        ), $args );

        $this->convert_tags = new WP_Ulike_Pro_Convert_Tags( $args, $extra_vars );
    }

    /**
     * Mandrill compatibility
     *
     * @param $nl2br
     * @param string $message
     * @return bool
     */
    function mandrill_nl2br( $nl2br, $message = '' ) {
        // text emails
        if ( ! WP_Ulike_Pro_Options::supportHtmlEmail() ) {
            $nl2br = true;
        }

        return $nl2br;
    }

    public static function get_template( $template_name ){
        // Get default template path
        $template_path = WP_ULIKE_PRO_PUBLIC_DIR . '/templates/email/'. $template_name . '.php';

        if( ! file_exists( $template_path ) ){
            return null;
        }

        return file_get_contents( $template_path, true );
    }


    /**
     * @param $slug
     * @param $args
     * @return bool|string
     */
    function get_email_template( $slug, $args = array() ) {
        // Get email template from options
        $template = WP_Ulike_Pro_Options::getEmailTemplate( $slug );

        if ( empty( $template ) ) {
            $template = self::get_template( $slug );
        }

        if ( empty( $template ) ) {
            _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $slug ), '2.1' );
            return false;
        }

        return apply_filters( 'wp_ulike_pro_email_template', $template, $slug, $args );
    }

    /**
     * Prepare email template to send
     *
     * @param $slug
     * @param $args
     * @return mixed|string
     */
    function prepare_template( $slug, $args = array() ) {
        ob_start();

        if ( WP_Ulike_Pro_Options::supportHtmlEmail() ) {

            echo apply_filters( 'wp_ulike_pro_email_template_html_formatting', '<html>', $slug, $args );

            do_action( 'wp_ulike_pro_before_email_template_body', $slug, $args );

            $body_attrs = apply_filters( 'wp_ulike_pro_email_template_body_attrs', 'style="background: #f2f2f2;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;"', $slug, $args );
            ?>

<body <?php echo $body_attrs ?>>

    <?php echo $this->get_email_template( $slug, $args ); ?>

</body>

</html>

<?php } else {

            //strip tags in plain text email
            //important don't use HTML in plain text emails!
            $raw_email_template = $this->get_email_template( $slug, $args );
            $plain_email_template = strip_tags( $raw_email_template );
            if( $plain_email_template !== $raw_email_template ){
                $plain_email_template = preg_replace( array('/&nbsp;/mi', '/^\s+/mi'), array(' ', ''), $plain_email_template );
            }

            echo $plain_email_template;

        }

        $message = ob_get_clean();

        // Convert tags in email template
        return $this->convert_tags->convert( $message, $args );
    }


    /**
     * Send Email function
     *
     * @param string $email
     * @param null $template
     * @param array $args
     */
    function send( $email, $template, $args = array() ) {

        if ( ! is_email( $email ) ) {
            return;
        }

        $this->prepare_tags( $args );

        // Headers
        $headers = array();
        $headers[] = 'From: '. stripslashes( WP_Ulike_Pro_Options::getAppearsFrom() ) .' <'. WP_Ulike_Pro_Options::getAppearsEmail() .'>';
        if ( WP_Ulike_Pro_Options::supportHtmlEmail() ) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        }
        // Subject
        $subject = apply_filters( 'wp_ulike_pro_email_send_subject', WP_Ulike_Pro_Options::getEmailTemplate( $template, 'subject' ), $template );
        $subject = wp_unslash( $this->convert_tags->convert( $subject, $args ) );
        $subject = html_entity_decode( $subject, ENT_QUOTES, 'UTF-8' );
        // Message body
        $message = $this->prepare_template( $template, $args );

        // Send mail
        return wp_mail( $email, $subject, $message, $headers );
    }

}