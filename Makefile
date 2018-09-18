install:
	composer install
	ant configure build -Dprofile=docker
