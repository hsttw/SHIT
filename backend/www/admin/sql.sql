CREATE TABLE admin(
  id integer PRIMARY KEY AUTOINCREMENT,
  username varchar NOT NULL,
  password varchar NOT NULL,
  create_date datetime NOT NULL,
  update_date datetime NOT NULL,
  last_login_time datetime DEFAULT NULL,
  session varchar DEFAULT NULL
)
insert into admin (username, password, create_date, update_date) valuse("root", "$2y$12$t.DRQeI56uOvu3RJEKyW9u3bvnqBkJgM13lGuaoeE5kxjJN3NxGcC", "2015-06-13 17:43:35", "2015-06-13 17:43:35");