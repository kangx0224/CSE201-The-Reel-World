<?php
	// Load passwords
	require_once('password.php');

	// Connect to DB
	$mysqli = mysqli_connect($host, $user, $password, $database);
	if (mysqli_connect_errno($mysqli)) {
		// Throw error if failed
    	echo "Failed to connect to MySQL: " . mysqli_connect_error();
    	die;
	}

	// DB Functions (Add any new functions below this line)

	/*
	Fair warning for anyone who edits this file.
	Global variables in PhP are WACK. You need to use the following syntax to access them:
		$GLOBALS['varName'];

	Just trying to save you some time that I wasted, Goodluck!
	- Alex
	*/

final class MovieManager
{
    private $mysqli;

    public function __construct($mysqli_conn)
    {
        $this->mysqli = $mysqli_conn;
    }

    public function getAllMovies() {
		$statement = $this->mysqli->prepare("SELECT m.title, m.movieId, m.requestId, m.description, m.keywords, m.imdbLink, m.image, m. imageAddress, m.rating, m.isDeleted, GROUP_CONCAT(g.description) genre, (SELECT GROUP_CONCAT(a.actorName) FROM Actors a JOIN ActorMovie am ON am.actorId = a.actorId WHERE am.movieId = m.movieId) AS actors
			FROM Movies m
			JOIN GenreMovie gm ON gm.movieId = m.movieId
			JOIN Genres g ON g.genreId = gm.genreId
			GROUP BY m.title
			ORDER BY m.title;"); //Defining the query
		$statement->bind_result($movieId, $requestId, $title, $description, $keywords, $imdbLink, $image, $imageAddress, $rating, $isDeleted, $genre, $actors); // Binding the variablesX()
		$statement->execute(); // Executing the query
		return $statement; // Return the results from the query
	}

    // A function that gets all the movies from the database that match a string
    // $keyword is the string used to search the database
    public function getAllMoviesByKeyword($keyword) {
          $statement = $this->mysqli->prepare("SELECT m.title, m.movieId, m.requestId, m.description, m.keywords, m.imdbLink, m.image, m. imageAddress, m.rating, m.isDeleted, GROUP_CONCAT(g.description) genre, (SELECT GROUP_CONCAT(a.actorName) FROM Actors a JOIN ActorMovie am ON am.actorId = a.actorId WHERE am.movieId = m.movieId) AS actors
			FROM Movies m
			JOIN GenreMovie gm ON gm.movieId = m.movieId
			JOIN Genres g ON g.genreId = gm.genreId
            WHERE m.title LIKE '%$keyword%'
			GROUP BY m.title
			ORDER BY m.title;"); //Defining the query
		$statement->bind_result($movieId, $requestId, $title, $description, $keywords, $imdbLink, $image, $imageAddress, $rating, $isDeleted, $genre, $actors); // Binding the variablesX()
		$statement->execute(); // Executing the query
		return $statement; // Return the results from the query    
    }
    // A function that gets all the movies from the database and orders them by their rating
    public function getAllMoviesByRating() {
		$statement = $this->mysqli->prepare("SELECT m.title, m.movieId, m.requestId, m.description, m.keywords, m.imdbLink, m.image, m. imageAddress, m.rating, m.isDeleted, GROUP_CONCAT(g.description) genre, (SELECT GROUP_CONCAT(a.actorName) FROM Actors a JOIN ActorMovie am ON am.actorId = a.actorId WHERE am.movieId = m.movieId) AS actors
			FROM Movies m
			JOIN GenreMovie gm ON gm.movieId = m.movieId
			JOIN Genres g ON g.genreId = gm.genreId
			GROUP BY m.title
			ORDER BY m.rating DESC;"); //Defining the query
		$statement->bind_result($movieId, $requestId, $title, $description, $keywords, $imdbLink, $image, $imageAddress, $rating, $isDeleted, $genre, $actors); // Binding the variablesX()
		$statement->execute(); // Executing the query
		return $statement; // Return the results from the query
	}

    public function getAllGenres() {
        $statement = $this->mysqli->prepare("SELECT DISTINCT genreId, description FROM Genres;");
        //Defining the query
		$statement->bind_result($genreId, $description); // Binding the variables
		$statement->execute(); // Executing the query
		return $statement; // Return the results from the query
    }

    public function getAllActors() {
        $statement = $this->mysqli->prepare("SELECT DISTINCT actorId, actorName FROM Actors WHERE isDeleted = false;");
        //Defining the query
		$statement->bind_result($actorId, $actorName); // Binding the variables
		$statement->execute(); // Executing the query
		return $statement; // Return the results from the query
    }

    public function getCheckedGenres($genreList) {

        $sql = "SELECT MAX(m.title), m.description, m.keywords, m.imdbLink, m.image, m.imageAddress, m.rating, m.isDeleted, GROUP_CONCAT(g.description) genre, (SELECT GROUP_CONCAT(a.actorName) FROM Actors a JOIN ActorMovie am ON am.actorId = a.actorId WHERE am.movieId = m.movieId) AS actors , g.isDeleted gDeleted
        FROM Movies m
        JOIN GenreMovie gm ON m.movieId = gm.movieId
        JOIN Genres g ON g.genreId = gm.genreId
        WHERE g.description IN ('" . implode("','", $genreList) . "')
        GROUP BY m.title;";

        if (!($statement = $this->mysqli->prepare($sql))) {
            echo "prepare fail" . $mysqli->error;
        }
        //Defining the query
		$statement->bind_result($title, $description, $keywords, $imdbLink, $image, $imageAddress, $rating, $isDeleted, $genre, $actors, $gDeleted ); // Binding the variables
		$statement->execute(); // Executing the query
		return $statement; // Return the results from the query
    }

    public function getSingleMovie($movieTitle) {
		$statement = $this->mysqli->prepare("SELECT m.title, m.movieId, m.requestId, m.description, m.keywords, m.imdbLink, m.image, m.imageAddress, m.rating, m.isDeleted, GROUP_CONCAT(g.description) genre, (SELECT GROUP_CONCAT(a.actorName) FROM Actors a JOIN ActorMovie am ON am.actorId = a.actorId WHERE am.movieId = m.movieId) AS actors
			FROM Movies m
			JOIN GenreMovie gm ON gm.movieId = m.movieId
			JOIN Genres g ON g.genreId = gm.genreId
			WHERE m.title = ?
			GROUP BY m.title
			ORDER BY m.title;"); //Defining the query
		$statement->bind_param("s", $movieTitle);
		$statement->bind_result($movieId, $requestId, $title, $description, $keywords, $imdbLink, $image, $imageAddress, $rating, $isDeleted, $genre, $actors); // Binding the variablesX()
		$statement->execute();
		return $statement;
	}
}

final class UserManager
{
    private $mysqli;

    public function __construct($mysqli_conn)
    {
        $this->mysqli = $mysqli_conn;
    }

    public function login($userName, $password) {

        $sql = "SELECT u.userId, u.userName, u.displayName, r.roleName
        FROM Users u
        JOIN Roles r ON u.roleId = r.roleId
        WHERE (u.userName = '".$userName."') AND (u.password = '".$password."') AND (u.isDeleted = false);";

        if (!($statement = $this->mysqli->prepare($sql))) {
            echo "prepare fail" . $mysqli->error;
        }
        //Defining the query
				$statement->bind_result($userId, $userName, $displayName, $roleName); // Binding the variables
				$statement->execute(); // Executing the query
				return $statement; // Return the results from the query
    }

		public function signup($userName, $password, $displayName) {
			$sql = "INSERT INTO Users(userName, password, displayName)
		 VALUES ('".$userName."', '".$password."', '".$displayName."');";

		 if ($this->mysqli->query($sql) === TRUE) {
				 echo "1";
		 } else {
				 echo "Error: " . $sql . "<br>" . $this->mysqli->error;
		 }
		}

		public function checkUsername($userName) {
			$sql = "SELECT userId FROM Users WHERE userName = '".$userName."';";
			if (!($statement = $this->mysqli->prepare($sql))) {
					echo "prepare fail" . $mysqli->error;
			}

			$statement -> bind_result($userId);
			$statement -> execute();
			$result = $statement->get_result();
			echo $result-> num_rows;
		}
}

final class RequestManager
{
	private $mysqli;

	public function __construct($mysqli_conn)
	{
			$this->mysqli = $mysqli_conn;
	}

	public function saveRequest($userId, $requestName, $description) {
		$sql = "INSERT INTO Requests(userId, requestName, description)
		VALUES (".$userId.", '".$requestName."', '".$description."');";

		if ($this->mysqli->query($sql) === TRUE) {
		    echo "New record created successfully";
		} else {
		    echo "Error: " . $sql . "<br>" . $this->mysqli->error;
		}
	}

	public function getRequests($userId) {
		$sql = "SELECT r.requestId, r.requestDate, r.requestName, r.description, (SELECT statusDescription FROM Status s WHERE s.statusId=r.statusId) AS status FROM Requests r WHERE r.userId=".$userId.";";

		if (!($statement = $this -> mysqli -> prepare($sql))) {
			echo "prepare fail" . $mysqli->error;
		}

		$statement -> bind_result($requestId, $requestDate, $requestName, $description, $status);
		$statement -> execute();
		return $statement;
	}

	public function deleteRequest($requestId) {
		$sql = "DELETE FROM Requests WHERE requestId = ".$requestId.";";

		if ($this->mysqli->query($sql) === TRUE) {
		    echo $requestId." deleted";
		} else {
		    echo "Error: " . $sql . "<br>" . $this->mysqli->error;
		}
	}
}

final class CommentManager
{
	private $mysqli;

	public function __construct($mysqli_conn) {
		$this -> mysqli = $mysqli_conn;
	}

	public function getCommentsByMovie ($movieId) {
		$sql = "SELECT c.commentId, u.userName, u.displayName, c.commentText
		FROM Comments c
		JOIN Users u ON c.userId = u.userId
		WHERE c.movieId = ".$movieId."
		GROUP BY c.commentId
		ORDER BY c.commentId;";

		// if (!()) {
		// 	echo "prepare fail" . $mysqli->error;
		// }

		$statement = $this -> mysqli -> prepare($sql);
		$statement -> bind_result($commentId, $userName, $displayName, $commentText);
		$statement -> execute();
		return $statement;
	}

	public function addComment($userId, $movieId, $commentText) {
		$sql = "INSERT INTO Comments(userId, movieId, commentText)
		VALUES (".$userId.", ".$movieId.", '".$commentText."');";

		if ($this->mysqli->query($sql) === TRUE) {
		    echo "New comment added successfully";
		} else {
		    echo "Error: " . $sql . "<br>" . $this->mysqli->error;
		}
	}
}
?>
