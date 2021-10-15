#!/bin/bash

for id in $(seq 6924 7195); do 
	echo $id
	#timeout 6 bndrimg ../images/logo_500.png --output=./${id}.png --seed=$id && convert -type Grayscale ./${id}.png ./${id}.png
	glitch_this ../images/logo_500.jpg 8 --outfile=./${id}.png --seed=$id 
done;
