
-- get all post for each user
DROP PROCEDURE if EXISTS user_posts;
DELIMITER &&
CREATE PROCEDURE user_posts(
    IN id int,
    IN startFrom int
)
BEGIN
	select posts.id,posts.user_id,posts.description,posts.user_id,posts.type,posts.created_at ,files.file,files.type from posts
    inner join files on posts.file_id = files.id WHERE posts.user_id=id ORDER by posts.created_at DESC LIMIT startFrom,10;
END &&
DELIMITER ;

-- get post count for each user 
DROP FUNCTION IF EXISTS	user_posts_count;
CREATE FUNCTION user_posts_count(user_id_p int)
RETURNS int(25)
	RETURN (SELECT count(*) as posts_count from posts WHERE user_id=user_id_p and is_group_post = false)

-- get friends count for each user 
DROP FUNCTION IF EXISTS	user_friends_count;
CREATE FUNCTION user_friends_count(user_id_p int)
RETURNS int(25)
	RETURN (SELECT count(*) from friends WHERE (request_from=user_id_p or request_to=user_id_p) and status=true)

-- get friends requests count for each user 
DROP FUNCTION IF EXISTS	user_requests_count;
CREATE FUNCTION user_requests_count(user_id_p int)
RETURNS int(25)
	RETURN (SELECT count(*) as requests_count from friends WHERE (request_to=user_id_p) and status=false);

-- get user profile image path
DROP FUNCTION IF EXISTS user_profile_img;
CREATE FUNCTION user_profile_img(user_id_p INT) RETURNS VARCHAR(255) RETURN(
    SELECT
        files.file
    FROM
        files
    INNER JOIN users ON users.profile_img = files.id
    WHERE
        users.id = user_id_p
);

-- get user cover image path
DROP FUNCTION IF EXISTS user_cover_img;
CREATE FUNCTION user_cover_img(user_id_p INT) 
RETURNS VARCHAR(255) RETURN(
    SELECT
        files.file
    FROM
        files
    INNER JOIN users ON users.cover_img = files.id
    WHERE
        users.id = user_id_p
);

-- get user breed name
DROP FUNCTION IF EXISTS user_breed_name;
CREATE FUNCTION user_breed_name(user_id_p INT) 
RETURNS VARCHAR(255) RETURN(
    SELECT
        animals_breeds.name
    FROM
        animals_breeds
    INNER JOIN users ON users.breed_id = animals_breeds.id
    WHERE
        users.id = user_id_p
);


-- get profile info
DROP PROCEDURE IF EXISTS user_profile_info;
DELIMITER &&
CREATE PROCEDURE user_profile_info(IN user_id_p INT)
BEGIN
    SELECT
        id,
        name,
        email,
        address,
        phone,
        birthday,
        gender,
        created_at,
        user_breed_name(user_id_p) as breed ,
        user_cover_img(user_id_p) AS cover_img,
        user_profile_img(user_id_p) AS profile_img,
        user_friends_count(user_id_p) AS friends_count,
        user_posts_count(user_id_p) AS posts_count
    FROM
        users
    WHERE id=user_id_p LIMIT 1;
END &&
DELIMITER ;

-- get all user friend requests info
DROP PROCEDURE IF EXISTS user_friend_requests;
DELIMITER
    &&
CREATE PROCEDURE user_friend_requests(IN user_id_p INT)
BEGIN
    SELECT
        users.id AS user_id,
        users.name,
        animals_breeds.name AS breed_name,
        files.file AS profile_img,
        friends.created_at sent_at,
        friends.id as request_id
    FROM
        users
    INNER JOIN animals_breeds ON users.breed_id = animals_breeds.id
    INNER JOIN files ON users.profile_img = files.id
    INNER JOIN friends ON( users.id = friends.request_from OR users.id = friends.request_to)
    WHERE
        users.id != user_id_p 
        AND (friends.request_to = user_id_p) 
        AND friends.status = false;
END &&
DELIMITER ;
-- get all user friends info //online
DROP PROCEDURE IF EXISTS user_online_friends;
DELIMITER
    &&
CREATE PROCEDURE user_online_friends(IN user_id_p INT ,IN c_time INT)
BEGIN
    SELECT
        c_time-users.last_login as last_login_sec,
        users.id AS user_id,
        users.name,
        animals_breeds.name AS breed_name,
        files.file AS profile_img
    FROM
        users
    INNER JOIN animals_breeds ON users.breed_id = animals_breeds.id
    INNER JOIN files ON users.profile_img = files.id
    INNER JOIN friends ON( users.id = friends.request_from OR users.id = friends.request_to)
    WHERE
        users.id != user_id_p 
        AND (friends.request_from = user_id_p OR friends.request_to = user_id_p) 
        AND friends.status = true;
END &&
DELIMITER ;

-- get all conversation with users
DROP
PROCEDURE IF EXISTS conversation_messages;
DELIMITER
    &&
CREATE PROCEDURE conversation_messages(
    IN user_id_p INT,
    IN reciever_id_p INT
)
BEGIN
    SELECT
        messages.content,
        messages.user_id AS send_by,
        messages.reciever_id AS send_to,
        messages.type AS message_type,
        messages.created_at as created_at,
        files.file AS message_img
    FROM
        messages
    INNER JOIN files ON messages.file_id = files.id
    WHERE (messages.user_id=user_id_p AND messages.reciever_id=reciever_id_p) 
            OR
           (messages.user_id=reciever_id_p AND messages.reciever_id=user_id_p ) ;
END &&
DELIMITER ;
-- 


-- ================================================================
SELECT
    posts.id as post_id,
    posts.user_id,
    posts.description,
    posts.type as post_type,
    posts.created_at as created_at,
    files.file as post_file
FROM
    posts
    INNER JOIN files ON files.id = posts.file_id
    INNER JOIN friends ON (friends.request_from = 4 and friends.request_to = posts.user_id )
    or (friends.request_to = 4 and friends.request_from = posts.user_id )
    AND (friends.status = true)
    WHERE posts.user_id != 4 and posts.post_id not in (1,2,3)
    ORDER BY RAND()
	LIMIT 10;
    -- =============================
-- get all post comments
DROP PROCEDURE IF EXISTS post_comments;
DELIMITER
    &&
CREATE PROCEDURE post_comments(
    IN post_id_p INT
)
BEGIN
    SELECT
        comments.id,
        comments.content,
        comments.has_reply,
        comments.created_at ,
        user_profile_img(comments.user_id) as profile_img ,
        comments.user_id,
        users.name
    FROM
        comments
        inner join users on comments.user_id=users.id
    WHERE
        comments.post_id = 58;
END &&
DELIMITER ;
-- get all comment replies
DROP PROCEDURE IF EXISTS comment_replies;
DELIMITER
    &&
CREATE PROCEDURE comment_replies(
    IN comment_id_p INT
)
BEGIN
    SELECT
        comments_replies.id,
        comments_replies.content,
        comments_replies.created_at ,
        user_profile_img(comments_replies.user_id) as profile_img ,
        comments_replies.user_id,
        users.name
    FROM
        comments_replies
        inner join users on comments_replies.user_id=users.id
    WHERE
        comments_replies.comment_id = comment_id_p
   ORDER BY comments_replies.created_at DESC ;
END &&
DELIMITER ;

-- check if user liked a post requests
DROP FUNCTION IF EXISTS post_liked;

DELIMITER //
CREATE FUNCTION post_liked(user_id_p INT,post_id_p INT) RETURNS BOOLEAN
BEGIN
  RETURN (SELECT COUNT(*) > 0 FROM likes WHERE ( user_id=user_id_p and post_id=post_id_p) );
END //
DELIMITER ;

select post_liked(4,58) as liked from likes
-- =============================================================
DELIMITER $$
CREATE PROCEDURE `conversation_user_info`(IN `user_id_p` INT)
BEGIN
    SELECT
        id,
        name,
        users.last_login,
        user_breed_name(user_id_p) as breed ,
        user_profile_img(user_id_p) AS profile_img
        
    FROM
        users
    WHERE id=user_id_p LIMIT 1;
END$$
DELIMITER ;
-- ================================================