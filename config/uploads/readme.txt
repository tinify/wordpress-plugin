Unfortunately, with newer Wordpress docker images it seems that the user
running Wordpress has no rights to create the uploads folder. This causes
many integration tests to fail.
Mounting this uploads folder as a volume in the docker container seem to work,
but ideally we'd like to change the docker files to address this issue.
