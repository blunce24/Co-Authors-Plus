<?php

class Test_Template_Tags extends CoAuthorsPlus_TestCase {

	public function setUp() {

		parent::setUp();

		$this->author1 = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'author1' ) );
		$this->editor1 = $this->factory->user->create( array( 'role' => 'editor', 'user_login' => 'editor1' ) );

		$this->post_id = wp_insert_post( array(
			'post_author'  => $this->author1,
			'post_status'  => 'publish',
			'post_content' => rand_str(),
			'post_title'   => rand_str(),
			'post_type'    => 'post',
		) );
	}

	/**
	 * Checks coauthors when post not exist.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::get_coauthors()
	 */
	public function test_get_coauthors_when_post_not_exists() {

		$this->assertEmpty( get_coauthors() );
	}

	/**
	 * Checks coauthors when post exist (not global).
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::get_coauthors()
	 */
	public function test_get_coauthors_when_post_exists() {

		global $coauthors_plus;

		// Compare single author.
		$this->assertEquals( array( $this->author1 ), wp_list_pluck( get_coauthors( $this->post_id ), 'ID' ) );

		// Compare multiple authors.
		$editor1 = get_user_by( 'id', $this->editor1 );
		$coauthors_plus->add_coauthors( $this->post_id, array( $editor1->user_login ), true );
		$this->assertEquals( array( $this->author1, $this->editor1 ), wp_list_pluck( get_coauthors( $this->post_id ), 'ID' ) );
	}

	/**
	 * Checks coauthors when terms for post not exist.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::get_coauthors()
	 */
	public function test_get_coauthors_when_terms_for_post_not_exists() {

		$post_id = $this->factory->post->create();
		$this->assertEmpty( get_coauthors( $post_id ) );
	}

	/**
	 * Checks coauthors when post not exist.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::get_coauthors()
	 */
	public function test_get_coauthors_when_global_post_exists() {

		global $post;

		// Backing up global post.
		$post_backup = $post;

		$post_id = $this->factory->post->create();
		$post    = get_post( $post_id );

		$this->assertEmpty( get_coauthors( $post_id ) );

		$user_id = $this->factory->user->create();
		$post_id = $this->factory->post->create( array(
			'post_author' => $user_id,
		) );
		$post    = get_post( $post_id );

		$this->assertEquals( array( $user_id ), wp_list_pluck( get_coauthors( $post_id ), 'ID' ) );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks whether user is a coauthor of the post when user or post not exists.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::is_coauthor_for_post()
	 */
	public function test_is_coauthor_for_post_when_user_or_post_not_exists() {

		global $post;

		// Backing up global post.
		$post_backup = $post;

		$this->assertFalse( is_coauthor_for_post( '' ) );
		$this->assertFalse( is_coauthor_for_post( '', $this->post_id ) );
		$this->assertFalse( is_coauthor_for_post( $this->author1 ) );

		$post = get_post( $this->post_id );

		$this->assertFalse( is_coauthor_for_post( '' ) );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks whether user is a coauthor of the post when user is not expected as ID,
	 * or user_login is not set in user object.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::is_coauthor_for_post()
	 */
	public function test_is_coauthor_for_post_when_user_not_numeric_or_user_login_not_set() {

		$this->assertFalse( is_coauthor_for_post( 'test' ) );
	}

	/**
	 * Checks whether user is a coauthor of the post when user is set in either way,
	 * as user_id or user object but he/she is not coauthor of the post.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::is_coauthor_for_post()
	 */
	public function test_is_coauthor_for_post_when_user_numeric_or_user_login_set_but_no_coauthor() {

		$this->assertFalse( is_coauthor_for_post( $this->editor1, $this->post_id ) );
		$this->assertFalse( is_coauthor_for_post( get_user_by( 'id', $this->editor1 ), $this->post_id ) );
	}

	/**
	 * Checks whether user is a coauthor of the post.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::is_coauthor_for_post()
	 */
	public function test_is_coauthor_for_post_when_user_is_coauthor() {

		global $post, $coauthors_plus;

		// Backing up global post.
		$post_backup = $post;

		$author1 = get_user_by( 'id', $this->author1 );
		$editor1 = get_user_by( 'id', $this->editor1 );

		$this->assertTrue( is_coauthor_for_post( $this->author1, $this->post_id ) );
		$this->assertTrue( is_coauthor_for_post( $author1, $this->post_id ) );

		$coauthors_plus->add_coauthors( $this->post_id, array( $editor1->user_login ), true );

		$this->assertTrue( is_coauthor_for_post( $this->editor1, $this->post_id ) );
		$this->assertTrue( is_coauthor_for_post( $editor1, $this->post_id ) );

		$post = get_post( $this->post_id );

		$this->assertTrue( is_coauthor_for_post( $this->author1 ) );
		$this->assertTrue( is_coauthor_for_post( $author1 ) );

		$this->assertTrue( is_coauthor_for_post( $this->editor1 ) );
		$this->assertTrue( is_coauthor_for_post( $editor1 ) );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Tests for co-authors display names, without links to their posts.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors()
	 **/
	public function test_coauthors() {

		global $post, $coauthors_plus;

		// Backing up global post.
		$post_backup = $post;

		$post    = get_post( $this->post_id );
		$author1 = get_user_by( 'id', $this->author1 );
		$editor1 = get_user_by( 'id', $this->editor1 );

		// Checks for single post author.
		$coauthors = coauthors( null, null, null, null, false );

		$this->assertEquals( $author1->display_name, $coauthors );
		$this->assertEquals( 1, substr_count( $coauthors, $author1->display_name ) );

		$coauthors = coauthors( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $author1->display_name . '</span>', $coauthors );
		$this->assertEquals( 1, substr_count( $coauthors, $author1->display_name ) );

		// Checks for multiple post author.
		$coauthors_plus->add_coauthors( $this->post_id, array( $editor1->user_login ), true );

		$coauthors = coauthors( null, null, null, null, false );

		$this->assertEquals( $author1->display_name . ' and ' . $editor1->display_name, $coauthors );
		$this->assertEquals( 1, substr_count( $coauthors, $author1->display_name ) );
		$this->assertEquals( 1, substr_count( $coauthors, $editor1->display_name ) );

		$coauthors = coauthors( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $author1->display_name . '</span><span>' . $editor1->display_name . '</span>', $coauthors );
		$this->assertEquals( 1, substr_count( $coauthors, $author1->display_name ) );
		$this->assertEquals( 1, substr_count( $coauthors, $editor1->display_name ) );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks single co-author linked to their post archive.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_posts_links_single()
	 */
	public function test_coauthors_posts_links_single() {

		$author1     = get_user_by( 'id', $this->author1 );
		$author_link = coauthors_posts_links_single( $author1 );

		$this->assertContains( 'href="' . get_author_posts_url( $author1->ID, $author1->user_nicename ) . '"', $author_link, 'Author link not found.' );
		$this->assertContains( $author1->display_name, $author_link, 'Author name not found.' );

		// Here we are checking author name should not be more then one time.
		// Asserting ">{$author1->display_name}<" because "$author1->display_name" can be multiple times like in href, title, etc.
		$this->assertEquals( 1, substr_count( $author_link, ">{$author1->display_name}<" ) );
	}

	/**
	 * Checks co-authors first names, without links to their posts.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_firstnames()
	 */
	public function test_coauthors_firstnames() {

		global $post, $coauthors_plus;

		// Backing up global post.
		$post_backup = $post;

		$post    = get_post( $this->post_id );
		$author1 = get_user_by( 'id', $this->author1 );
		$editor1 = get_user_by( 'id', $this->editor1 );

		$first_names = coauthors_firstnames( null, null, null, null, false );

		$this->assertEquals( $author1->user_login, $first_names );
		$this->assertEquals( 1, substr_count( $first_names, $author1->user_login ) );

		$first_names = coauthors_firstnames( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $author1->user_login . '</span>', $first_names );
		$this->assertEquals( 1, substr_count( $first_names, $author1->user_login ) );

		$coauthors_plus->add_coauthors( $this->post_id, array( $editor1->user_login ), true );

		$first_names = coauthors_firstnames( null, null, null, null, false );

		$this->assertEquals( $author1->user_login . ' and ' . $editor1->user_login, $first_names );
		$this->assertEquals( 1, substr_count( $first_names, $author1->user_login ) );
		$this->assertEquals( 1, substr_count( $first_names, $editor1->user_login ) );

		$first_names = coauthors_firstnames( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $author1->user_login . '</span><span>' . $editor1->user_login . '</span>', $first_names );
		$this->assertEquals( 1, substr_count( $first_names, $author1->user_login ) );
		$this->assertEquals( 1, substr_count( $first_names, $editor1->user_login ) );

		$first_name = 'Test';
		$user_id    = $this->factory->user->create( array(
			'first_name' => $first_name,
		) );
		$post_id    = $this->factory->post->create( array(
			'post_author' => $user_id,
		) );
		$post       = get_post( $post_id );

		$first_names = coauthors_firstnames( null, null, null, null, false );

		$this->assertEquals( $first_name, $first_names );
		$this->assertEquals( 1, substr_count( $first_names, $first_name ) );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks co-authors last names, without links to their posts.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_lastnames()
	 */
	public function test_coauthors_lastnames() {

		global $post, $coauthors_plus;

		// Backing up global post.
		$post_backup = $post;

		$post    = get_post( $this->post_id );
		$author1 = get_user_by( 'id', $this->author1 );
		$editor1 = get_user_by( 'id', $this->editor1 );

		$last_names = coauthors_lastnames( null, null, null, null, false );

		$this->assertEquals( $author1->user_login, $last_names );
		$this->assertEquals( 1, substr_count( $last_names, $author1->user_login ) );

		$last_names = coauthors_lastnames( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $author1->user_login . '</span>', $last_names );
		$this->assertEquals( 1, substr_count( $last_names, $author1->user_login ) );

		$coauthors_plus->add_coauthors( $this->post_id, array( $editor1->user_login ), true );

		$last_names = coauthors_lastnames( null, null, null, null, false );

		$this->assertEquals( $author1->user_login . ' and ' . $editor1->user_login, $last_names );
		$this->assertEquals( 1, substr_count( $last_names, $author1->user_login ) );
		$this->assertEquals( 1, substr_count( $last_names, $editor1->user_login ) );

		$last_names = coauthors_lastnames( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $author1->user_login . '</span><span>' . $editor1->user_login . '</span>', $last_names );
		$this->assertEquals( 1, substr_count( $last_names, $author1->user_login ) );
		$this->assertEquals( 1, substr_count( $last_names, $editor1->user_login ) );

		$last_name = 'Test';
		$user_id   = $this->factory->user->create( array(
			'last_name' => $last_name,
		) );
		$post_id   = $this->factory->post->create( array(
			'post_author' => $user_id,
		) );
		$post      = get_post( $post_id );

		$last_names = coauthors_lastnames( null, null, null, null, false );

		$this->assertEquals( $last_name, $last_names );
		$this->assertEquals( 1, substr_count( $last_names, $last_name ) );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks co-authors nicknames, without links to their posts.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_nicknames()
	 */
	public function test_coauthors_nicknames() {

		global $post, $coauthors_plus;

		// Backing up global post.
		$post_backup = $post;

		$post    = get_post( $this->post_id );
		$author1 = get_user_by( 'id', $this->author1 );
		$editor1 = get_user_by( 'id', $this->editor1 );

		$nick_names = coauthors_nicknames( null, null, null, null, false );

		$this->assertEquals( $author1->user_login, $nick_names );
		$this->assertEquals( 1, substr_count( $nick_names, $author1->user_login ) );

		$nick_names = coauthors_nicknames( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $author1->user_login . '</span>', $nick_names );
		$this->assertEquals( 1, substr_count( $nick_names, $author1->user_login ) );

		$coauthors_plus->add_coauthors( $this->post_id, array( $editor1->user_login ), true );

		$nick_names = coauthors_nicknames( null, null, null, null, false );

		$this->assertEquals( $author1->user_login . ' and ' . $editor1->user_login, $nick_names );
		$this->assertEquals( 1, substr_count( $nick_names, $author1->user_login ) );
		$this->assertEquals( 1, substr_count( $nick_names, $editor1->user_login ) );

		$nick_names = coauthors_nicknames( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $author1->user_login . '</span><span>' . $editor1->user_login . '</span>', $nick_names );
		$this->assertEquals( 1, substr_count( $nick_names, $author1->user_login ) );
		$this->assertEquals( 1, substr_count( $nick_names, $editor1->user_login ) );

		$nick_name = 'Test';
		$user_id   = $this->factory->user->create( array(
			'nickname' => $nick_name,
		) );
		$post_id   = $this->factory->post->create( array(
			'post_author' => $user_id,
		) );
		$post      = get_post( $post_id );

		$nick_names = coauthors_nicknames( null, null, null, null, false );

		$this->assertEquals( $nick_name, $nick_names );
		$this->assertEquals( 1, substr_count( $nick_names, $nick_name ) );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks co-authors email addresses.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_emails()
	 */
	public function test_coauthors_emails() {

		global $post, $coauthors_plus;

		// Backing up global post.
		$post_backup = $post;

		$post    = get_post( $this->post_id );
		$author1 = get_user_by( 'id', $this->author1 );
		$editor1 = get_user_by( 'id', $this->editor1 );

		$emails = coauthors_emails( null, null, null, null, false );

		$this->assertEquals( $author1->user_email, $emails );
		$this->assertEquals( 1, substr_count( $emails, $author1->user_email ) );

		$emails = coauthors_emails( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $author1->user_email . '</span>', $emails );
		$this->assertEquals( 1, substr_count( $emails, $author1->user_email ) );

		$coauthors_plus->add_coauthors( $this->post_id, array( $editor1->user_login ), true );

		$emails = coauthors_emails( null, null, null, null, false );

		$this->assertEquals( $author1->user_email . ' and ' . $editor1->user_email, $emails );
		$this->assertEquals( 1, substr_count( $emails, $author1->user_email ) );
		$this->assertEquals( 1, substr_count( $emails, $editor1->user_email ) );

		$emails = coauthors_emails( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $author1->user_email . '</span><span>' . $editor1->user_email . '</span>', $emails );
		$this->assertEquals( 1, substr_count( $emails, $author1->user_email ) );
		$this->assertEquals( 1, substr_count( $emails, $editor1->user_email ) );

		$email   = 'test@example.org';
		$user_id = $this->factory->user->create( array(
			'user_email' => $email,
		) );
		$post_id = $this->factory->post->create( array(
			'post_author' => $user_id,
		) );
		$post    = get_post( $post_id );

		$emails = coauthors_emails( null, null, null, null, false );

		$this->assertEquals( $email, $emails );
		$this->assertEquals( 1, substr_count( $emails, $email ) );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks single co-author if he/she is a guest author.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_links_single()
	 */
	public function test_coauthors_links_single_when_guest_author() {

		global $authordata;

		// Backing up global author data.
		$authordata_backup = $authordata;

		$author1       = get_user_by( 'id', $this->author1 );
		$author1->type = 'guest-author';

		$this->assertNull( coauthors_links_single( $author1 ) );

		update_user_meta( $this->author1, 'website', 'example.org' );

		$this->assertNull( coauthors_links_single( $author1 ) );

		$authordata  = $author1;
		$author_link = coauthors_links_single( $author1 );

		$this->assertContains( get_the_author_meta( 'website' ), $author_link, 'Author link not found.' );
		$this->assertContains( get_the_author(), $author_link, 'Author name not found.' );

		// Here we are checking author name should not be more then one time.
		// Asserting ">get_the_author()<" because "get_the_author()" can be multiple times like in href, title, etc.
		$this->assertEquals( 1, substr_count( $author_link, '>' . get_the_author() . '<' ) );

		// Restore global author data from backup.
		$authordata = $authordata_backup;
	}

	/**
	 * Checks single co-author when user's url is set and not a guest author.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_links_single()
	 */
	public function test_coauthors_links_single_author_url_is_set() {

		global $authordata;

		// Backing up global author data.
		$authordata_backup = $authordata;

		$user_id = $this->factory->user->create( array(
			'user_url' => 'example.org',
		) );
		$user    = get_user_by( 'id', $user_id );

		$authordata  = $user;
		$author_link = coauthors_links_single( $user );

		$this->assertContains( get_the_author_meta( 'url' ), $author_link, 'Author link not found.' );
		$this->assertContains( get_the_author(), $author_link, 'Author name not found.' );

		// Here we are checking author name should not be more then one time.
		// Asserting ">get_the_author()<" because "get_the_author()" can be multiple times like in href, title, etc.
		$this->assertEquals( 1, substr_count( $author_link, '>' . get_the_author() . '<' ) );

		// Restore global author data from backup.
		$authordata = $authordata_backup;
	}

	/**
	 * Checks single co-author when user's website/url not exist.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_links_single()
	 */
	public function test_coauthors_links_single_when_url_not_exist() {

		global $authordata;

		// Backing up global author data.
		$authordata_backup = $authordata;

		$author1     = get_user_by( 'id', $this->author1 );
		$author_link = coauthors_links_single( $author1 );

		$this->assertEmpty( $author_link );

		$authordata  = $author1;
		$author_link = coauthors_links_single( $author1 );

		$this->assertEquals( get_the_author(), $author_link );

		// Restore global author data from backup.
		$authordata = $authordata_backup;
	}

	/**
	 * Checks co-authors IDs.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_ids()
	 */
	public function test_coauthors_ids() {

		global $post, $coauthors_plus;

		// Backing up global post.
		$post_backup = $post;

		$post    = get_post( $this->post_id );
		$author1 = get_user_by( 'id', $this->author1 );
		$editor1 = get_user_by( 'id', $this->editor1 );

		$ids = coauthors_ids( null, null, null, null, false );

		$this->assertEquals( $author1->ID, $ids );
		$this->assertEquals( 1, substr_count( $ids, $author1->ID ) );

		$ids = coauthors_ids( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $author1->ID . '</span>', $ids );
		$this->assertEquals( 1, substr_count( $ids, $author1->ID ) );

		$coauthors_plus->add_coauthors( $this->post_id, array( $editor1->user_login ), true );

		$ids = coauthors_ids( null, null, null, null, false );

		$this->assertEquals( $author1->ID . ' and ' . $editor1->ID, $ids );
		$this->assertEquals( 1, substr_count( $ids, $author1->ID ) );
		$this->assertEquals( 1, substr_count( $ids, $editor1->ID ) );

		$ids = coauthors_ids( '</span><span>', '</span><span>', '<span>', '</span>', false );

		$this->assertEquals( '<span>' . $author1->ID . '</span><span>' . $editor1->ID . '</span>', $ids );
		$this->assertEquals( 1, substr_count( $ids, $author1->ID ) );
		$this->assertEquals( 1, substr_count( $ids, $editor1->ID ) );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks co-authors meta.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::get_the_coauthor_meta()
	 */
	public function test_get_the_coauthor_meta() {

		global $post;

		// Backing up global post.
		$post_backup = $post;

		$this->assertEmpty( get_the_coauthor_meta( '' ) );

		update_user_meta( $this->author1, 'test_meta', 'test_meta' );

		$this->assertEmpty( get_the_coauthor_meta( 'test_meta' ) );

		$post = get_post( $this->post_id );
		$meta = get_the_coauthor_meta( 'test_meta' );

		$this->assertEquals( 'test_meta', $meta[ $this->author1 ] );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks all the co-authors of the blog with default args.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_default_args() {

		global $coauthors_plus;

		$args = array(
			'echo' => false,
		);

		$author1   = get_user_by( 'id', $this->author1 );
		$editor1   = get_user_by( 'id', $this->editor1 );
		$coauthors = coauthors_wp_list_authors( $args );

		$this->assertContains( 'href="' . get_author_posts_url( $author1->ID, $author1->user_nicename ) . '"', $coauthors, 'Author link not found.' );
		$this->assertContains( $author1->display_name, $coauthors, 'Author name not found.' );

		$coauthors = coauthors_wp_list_authors( $args );

		$this->assertNotContains( 'href="' . get_author_posts_url( $editor1->ID, $editor1->user_nicename ) . '"', $coauthors );
		$this->assertNotContains( $editor1->display_name, $coauthors );

		$coauthors_plus->add_coauthors( $this->post_id, array( $editor1->user_login ), true );

		$coauthors = coauthors_wp_list_authors( $args );

		$this->assertContains( 'href="' . get_author_posts_url( $author1->ID, $author1->user_nicename ) . '"', $coauthors, 'Main author link not found.' );
		$this->assertContains( $author1->display_name, $coauthors, 'Main author name not found.' );

		// Here we are checking author name should not be more then one time.
		// Asserting ">{$author1->display_name}<" because "$author1->display_name" can be multiple times like in href, title, etc.
		$this->assertEquals( 1, substr_count( $coauthors, ">{$author1->display_name}<" ) );

		$this->assertContains( '</li><li>', $coauthors, 'Coauthors name separator is not matched.' );
		$this->assertContains( 'href="' . get_author_posts_url( $editor1->ID, $editor1->user_nicename ) . '"', $coauthors, 'Coauthor link not found.' );
		$this->assertContains( $editor1->display_name, $coauthors, 'Coauthor name not found.' );

		// Here we are checking editor name should not be more then one time.
		// Asserting ">{$editor1->display_name}<" because "$editor1->display_name" can be multiple times like in href, title, etc.
		$this->assertEquals( 1, substr_count( $coauthors, ">{$editor1->display_name}<" ) );
	}

	/**
	 * Checks all the co-authors of the blog with optioncount option.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_optioncount() {

		$this->assertContains( '(' . count_user_posts( $this->author1 ) . ')', coauthors_wp_list_authors( array(
			'echo'        => false,
			'optioncount' => true,
		) ) );
	}

	/**
	 * Checks all the co-authors of the blog with show_fullname option.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_show_fullname() {

		$args = array(
			'echo'          => false,
			'show_fullname' => true,
		);

		$author1 = get_user_by( 'id', $this->author1 );

		$this->assertContains( $author1->display_name, coauthors_wp_list_authors( $args ) );

		$user = $this->factory->user->create_and_get( array(
			'first_name' => 'First',
			'last_name'  => 'Last',
		) );

		$this->factory->post->create( array(
			'post_author' => $user->ID,
		) );

		$this->assertContains( "{$user->user_firstname} {$user->user_lastname}", coauthors_wp_list_authors( $args ) );
	}

	/**
	 * Checks all the co-authors of the blog with hide_empty option.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_hide_empty() {

		global $coauthors_plus;

		$coauthors_plus->guest_authors->create( array(
			'user_login'   => 'author2',
			'display_name' => 'author2',
		) );

		$this->assertContains( 'author2', coauthors_wp_list_authors( array(
			'echo'       => false,
			'hide_empty' => false,
		) ) );
	}

	/**
	 * Checks all the co-authors of the blog with feed option.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_feed() {

		$feed_text = 'link to feed';
		$coauthors = coauthors_wp_list_authors( array(
			'echo' => false,
			'feed' => $feed_text,
		) );

		$this->assertContains( get_author_feed_link( $this->author1 ), $coauthors );
		$this->assertContains( $feed_text, $coauthors );
	}

	/**
	 * Checks all the co-authors of the blog with feed_image option.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_feed_image() {

		$feed_image = WP_TESTS_DOMAIN . '/path/to/a/graphic.png';
		$coauthors  = coauthors_wp_list_authors( array(
			'echo'       => false,
			'feed_image' => $feed_image,
		) );

		$this->assertContains( get_author_feed_link( $this->author1 ), $coauthors );
		$this->assertContains( $feed_image, $coauthors );
	}

	/**
	 * Checks all the co-authors of the blog with feed_type option.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_feed_type() {

		$feed_type = 'atom';
		$feed_text = 'link to feed';
		$coauthors = coauthors_wp_list_authors( array(
			'echo'      => false,
			'feed_type' => $feed_type,
			'feed'      => $feed_text,
		) );

		$this->assertContains( get_author_feed_link( $this->author1, $feed_type ), $coauthors );
		$this->assertContains( $feed_type, $coauthors );
		$this->assertContains( $feed_text, $coauthors );
	}

	/**
	 * Checks all the co-authors of the blog with style option.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_style() {

		$coauthors = coauthors_wp_list_authors( array(
			'echo'  => false,
			'style' => 'none',
		) );

		$this->assertNotContains( '<li>', $coauthors );
		$this->assertNotContains( '</li>', $coauthors );
	}

	/**
	 * Checks all the co-authors of the blog with html option.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_html() {

		global $coauthors_plus;

		$args = array(
			'echo' => false,
			'html' => false,
		);

		$author1 = get_user_by( 'id', $this->author1 );
		$editor1 = get_user_by( 'id', $this->editor1 );

		$this->assertEquals( $author1->display_name, coauthors_wp_list_authors( $args ) );

		$coauthors_plus->add_coauthors( $this->post_id, array( $editor1->user_login ), true );

		$this->assertEquals( "$author1->display_name, $editor1->display_name", coauthors_wp_list_authors( $args ) );
	}

	/**
	 * Checks all the co-authors of the blog with guest_authors_only option.
	 *
	 * @see https://github.com/Automattic/Co-Authors-Plus/issues/184
	 *
	 * @covers ::coauthors_wp_list_authors()
	 */
	public function test_coauthors_wp_list_authors_for_guest_authors_only() {

		global $coauthors_plus;

		$args = array(
			'echo'               => false,
			'guest_authors_only' => true,
		);

		$this->assertEmpty( coauthors_wp_list_authors( $args ) );

		$guest_author_id = $coauthors_plus->guest_authors->create( array(
			'user_login'   => 'author2',
			'display_name' => 'author2',
		) );

		$this->assertEmpty( coauthors_wp_list_authors( $args ) );

		$guest_author = $coauthors_plus->guest_authors->get_guest_author_by( 'id', $guest_author_id );

		$coauthors_plus->add_coauthors( $this->post_id, array( $guest_author->user_login ), true );

		$this->assertEquals( $guest_author->display_name, coauthors_wp_list_authors( $args ) );
	}
}
