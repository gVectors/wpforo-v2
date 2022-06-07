<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

$fields      = wpforo_profile_fields();
$stat_topics = wpfval( WPF()->current_object['user'], 'topics' );
$stat_topics = $stat_topics ? (int) wpforo_print_number( $stat_topics ) : 0;
$rating_level = wpfval( WPF()->current_object['user'], 'rating', 'level' );
?>

<div class="wpforo-profile-home">

    <?php if( WPF()->usergroup->can( 'vmr' ) ): ?>
        <div class="wpf-profile-section wpf-section-stat">
            <div class="wpf-stat-wrap">
                <div class="wpf-statbox">
                    <div class="wpf-statbox-icon" style="background: #dff6ff; fill: #5bb9dc;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22.853,1.148a3.626,3.626,0,0,0-5.124,0L1.465,17.412A4.968,4.968,0,0,0,0,20.947V23a1,1,0,0,0,1,1H3.053a4.966,4.966,0,0,0,3.535-1.464L22.853,6.271A3.626,3.626,0,0,0,22.853,1.148ZM5.174,21.122A3.022,3.022,0,0,1,3.053,22H2V20.947a2.98,2.98,0,0,1,.879-2.121L15.222,6.483l2.3,2.3ZM21.438,4.857,18.932,7.364l-2.3-2.295,2.507-2.507a1.623,1.623,0,1,1,2.295,2.3Z"/></svg>
                    </div>
                    <div class="wpf-statbox-data">
                        <div class="wpf-statbox-value"><?php wpforo_print_number( wpfval( WPF()->current_object['user'], 'posts' ), true ) ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase( 'Forum Posts' ) ?></div>
                    </div>
                </div>
                <div class="wpf-statbox">
                    <div class="wpf-statbox-icon" style="background: #ffe4e1; fill: #cb8c84;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M1,6H23a1,1,0,0,0,0-2H1A1,1,0,0,0,1,6Z"/><path d="M23,9H9a1,1,0,0,0,0,2H23a1,1,0,0,0,0-2Z"/><path d="M23,19H9a1,1,0,0,0,0,2H23a1,1,0,0,0,0-2Z"/><path d="M23,14H1a1,1,0,0,0,0,2H23a1,1,0,0,0,0-2Z"/></svg>
                    </div>
                    <div class="wpf-statbox-data">
                        <div class="wpf-statbox-value"><?php echo $stat_topics ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase( 'Topics' ) ?></div>
                    </div>
                </div>
                <div class="wpf-statbox">
                    <div class="wpf-statbox-icon" style="background: #f6e1ff; fill: #cd8aef;">
                        <svg viewBox="0 0 320 512" xmlns="http://www.w3.org/2000/svg"><path d="M204.3 32.01H96c-52.94 0-96 43.06-96 96c0 17.67 14.31 31.1 32 31.1s32-14.32 32-31.1c0-17.64 14.34-32 32-32h108.3C232.8 96.01 256 119.2 256 147.8c0 19.72-10.97 37.47-30.5 47.33L127.8 252.4C117.1 258.2 112 268.7 112 280v40c0 17.67 14.31 31.99 32 31.99s32-14.32 32-31.99V298.3L256 251.3c39.47-19.75 64-59.42 64-103.5C320 83.95 268.1 32.01 204.3 32.01zM144 400c-22.09 0-40 17.91-40 40s17.91 39.1 40 39.1s40-17.9 40-39.1S166.1 400 144 400z"/></svg>
                    </div>
                    <div class="wpf-statbox-data">
                        <div class="wpf-statbox-value"><?php wpforo_print_number( wpfval( WPF()->current_object['user'], 'questions' ), true ) ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase( 'Questions' ) ?></div>
                    </div>
                </div>
                <div class="wpf-statbox">
                    <div class="wpf-statbox-icon" style="background: #e4ffca; fill:  #97d060;">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" viewBox="0 0 507.506 507.506" style="enable-background:new 0 0 507.506 507.506;" xml:space="preserve"><g><path d="M163.865,436.934c-14.406,0.006-28.222-5.72-38.4-15.915L9.369,304.966c-12.492-12.496-12.492-32.752,0-45.248l0,0   c12.496-12.492,32.752-12.492,45.248,0l109.248,109.248L452.889,79.942c12.496-12.492,32.752-12.492,45.248,0l0,0   c12.492,12.496,12.492,32.752,0,45.248L202.265,421.019C192.087,431.214,178.271,436.94,163.865,436.934z"/></g></svg>
                    </div>
                    <div class="wpf-statbox-data">
                        <div class="wpf-statbox-value"><?php wpforo_print_number( wpfval( WPF()->current_object['user'], 'answers' ), true ) ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase( 'Answers' ) ?></div>
                    </div>
                </div>
                <div class="wpf-statbox">
                    <div class="wpf-statbox-icon" style="background: #f8edad; fill:  #e8aa1d;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M24,11.247A12.012,12.012,0,1,0,12.017,24H19a5.005,5.005,0,0,0,5-5V11.247ZM22,19a3,3,0,0,1-3,3H12.017a10.041,10.041,0,0,1-7.476-3.343,9.917,9.917,0,0,1-2.476-7.814,10.043,10.043,0,0,1,8.656-8.761A10.564,10.564,0,0,1,12.021,2,9.921,9.921,0,0,1,18.4,4.3,10.041,10.041,0,0,1,22,11.342Z"/><path d="M8,9h4a1,1,0,0,0,0-2H8A1,1,0,0,0,8,9Z"/><path d="M16,11H8a1,1,0,0,0,0,2h8a1,1,0,0,0,0-2Z"/><path d="M16,15H8a1,1,0,0,0,0,2h8a1,1,0,0,0,0-2Z"/></svg>
                    </div>
                    <div class="wpf-statbox-data">
                        <div class="wpf-statbox-value"><?php wpforo_print_number( wpfval( WPF()->current_object['user'], 'comments' ), true ) ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase( 'Question Comments' ) ?></div>
                    </div>
                </div>
                <div class="wpf-statbox">
                    <div class="wpf-statbox-icon" style="background: #e0eaff; fill:  #567dbe;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g id="_01_align_center" data-name="01 align center"><path d="M15.021,7l.336-2.041a3.044,3.044,0,0,0-4.208-3.287A3.139,3.139,0,0,0,9.582,3.225L7.717,7H3a3,3,0,0,0-3,3v9a3,3,0,0,0,3,3H22.018L24,10.963,24.016,7ZM2,19V10A1,1,0,0,1,3,9H7V20H3A1,1,0,0,1,2,19Zm20-8.3L20.33,20H9V8.909l2.419-4.9A1.07,1.07,0,0,1,13.141,3.8a1.024,1.024,0,0,1,.233.84L12.655,9H22Z"/></g></svg>
                    </div>
                    <div class="wpf-statbox-data">
                        <div class="wpf-statbox-value"><?php wpforo_print_number( WPF()->current_object['user']['reactions_out']['__ALL__'], true ); ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase( 'Liked' ) ?></div>
                    </div>
                </div>
                <div class="wpf-statbox">
                    <div class="wpf-statbox-icon" style="background: #c4f0f3; fill:  #38a9c4;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="transform: scaleX(-1); -webkit-transform: scaleX(-1);"><g id="_01_align_center" data-name="01 align center"><path d="M15.021,7l.336-2.041a3.044,3.044,0,0,0-4.208-3.287A3.139,3.139,0,0,0,9.582,3.225L7.717,7H3a3,3,0,0,0-3,3v9a3,3,0,0,0,3,3H22.018L24,10.963,24.016,7ZM2,19V10A1,1,0,0,1,3,9H7V20H3A1,1,0,0,1,2,19Zm20-8.3L20.33,20H9V8.909l2.419-4.9A1.07,1.07,0,0,1,13.141,3.8a1.024,1.024,0,0,1,.233.84L12.655,9H22Z"/></g></svg>
                    </div>
                    <div class="wpf-statbox-data">
                        <div class="wpf-statbox-value"><?php wpforo_print_number( wpfval( WPF()->current_object['user']['reactions_in'], 'up' ), true ); ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase( 'Received Likes' ) ?></div>
                    </div>
                </div>
                <div class="wpf-statbox">
                    <div class="wpf-statbox-icon" style="background: #ffece8; fill:  #f85151;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="transform: rotate(180deg);"><g id="_01_align_center" data-name="01 align center"><path d="M15.021,7l.336-2.041a3.044,3.044,0,0,0-4.208-3.287A3.139,3.139,0,0,0,9.582,3.225L7.717,7H3a3,3,0,0,0-3,3v9a3,3,0,0,0,3,3H22.018L24,10.963,24.016,7ZM2,19V10A1,1,0,0,1,3,9H7V20H3A1,1,0,0,1,2,19Zm20-8.3L20.33,20H9V8.909l2.419-4.9A1.07,1.07,0,0,1,13.141,3.8a1.024,1.024,0,0,1,.233.84L12.655,9H22Z"/></g></svg>
                    </div>
                    <div class="wpf-statbox-data">
                        <div class="wpf-statbox-value"><?php wpforo_print_number( wpfval(WPF()->current_object['user']['reactions_in'], 'down' ), true ); ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase( 'Received Dislikes' ) ?></div>
                    </div>
                </div>
                <div class="wpf-statbox">
                    <div class="wpf-statbox-icon" style="background: #dffde8; fill:  #78cb90;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M23.836,8.794a3.179,3.179,0,0,0-3.067-2.226H16.4L15.073,2.432a3.227,3.227,0,0,0-6.146,0L7.6,6.568H3.231a3.227,3.227,0,0,0-1.9,5.832L4.887,15,3.535,19.187A3.178,3.178,0,0,0,4.719,22.8a3.177,3.177,0,0,0,3.8-.019L12,20.219l3.482,2.559a3.227,3.227,0,0,0,4.983-3.591L19.113,15l3.56-2.6A3.177,3.177,0,0,0,23.836,8.794Zm-2.343,1.991-4.144,3.029a1,1,0,0,0-.362,1.116L18.562,19.8a1.227,1.227,0,0,1-1.895,1.365l-4.075-3a1,1,0,0,0-1.184,0l-4.075,3a1.227,1.227,0,0,1-1.9-1.365L7.013,14.93a1,1,0,0,0-.362-1.116L2.507,10.785a1.227,1.227,0,0,1,.724-2.217h5.1a1,1,0,0,0,.952-.694l1.55-4.831a1.227,1.227,0,0,1,2.336,0l1.55,4.831a1,1,0,0,0,.952.694h5.1a1.227,1.227,0,0,1,.724,2.217Z"/></svg>
                    </div>
                    <div class="wpf-statbox-data">
                        <div class="wpf-statbox-value"><?php echo $rating_level ?>/10</div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase( 'Rating' ) ?></div>
                    </div>
                </div>
                <div class="wpf-statbox">
                    <div class="wpf-statbox-icon" style="background: #f3e2ff; fill:  #ba69f8;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.656.93,6.464,13.122A4.966,4.966,0,0,0,5,16.657V18a1,1,0,0,0,1,1H7.343a4.966,4.966,0,0,0,3.535-1.464L23.07,5.344a3.125,3.125,0,0,0,0-4.414A3.194,3.194,0,0,0,18.656.93Zm3,3L9.464,16.122A3.02,3.02,0,0,1,7.343,17H7v-.343a3.02,3.02,0,0,1,.878-2.121L20.07,2.344a1.148,1.148,0,0,1,1.586,0A1.123,1.123,0,0,1,21.656,3.93Z"/><path d="M23,8.979a1,1,0,0,0-1,1V15H18a3,3,0,0,0-3,3v4H5a3,3,0,0,1-3-3V5A3,3,0,0,1,5,2h9.042a1,1,0,0,0,0-2H5A5.006,5.006,0,0,0,0,5V19a5.006,5.006,0,0,0,5,5H16.343a4.968,4.968,0,0,0,3.536-1.464l2.656-2.658A4.968,4.968,0,0,0,24,16.343V9.979A1,1,0,0,0,23,8.979ZM18.465,21.122a2.975,2.975,0,0,1-1.465.8V18a1,1,0,0,1,1-1h3.925a3.016,3.016,0,0,1-.8,1.464Z"/></svg>
                    </div>
                    <div class="wpf-statbox-data">
                        <div class="wpf-statbox-value"><?php echo WPF()->member->blog_posts( WPF()->current_object['userid'] ) ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase( 'Blog Posts' ) ?></div>
                    </div>
                </div>
                <div class="wpf-statbox">
                    <div class="wpf-statbox-icon" style="background: #ffe7f6; fill:  #ee3ba9;">
                        <svg xmlns="http://www.w3.org/2000/svg" data-name="Layer 1" viewBox="0 0 24 24"><path d="M24,16v8H16a8,8,0,0,1-6.92-4,10.968,10.968,0,0,0,2.242-.248A5.988,5.988,0,0,0,16,22h6V16a5.988,5.988,0,0,0-2.252-4.678A10.968,10.968,0,0,0,20,9.08,8,8,0,0,1,24,16ZM18,9A9,9,0,0,0,0,9v9H9A9.01,9.01,0,0,0,18,9ZM2,9a7,7,0,1,1,7,7H2Z"/></svg>
                    </div>
                    <div class="wpf-statbox-data">
                        <div class="wpf-statbox-value"><?php echo WPF()->member->blog_comments( WPF()->current_object['userid'], wpfval( WPF()->current_object['user'], 'user_email' ) ) ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase( 'Blog Comments' ) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="wpf-profile-section wpf-mi-section">
        <div class="wpf-table">
			<?php wpforo_fields( $fields ); ?>
        </div>
    </div>



</div>
