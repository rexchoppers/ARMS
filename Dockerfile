FROM gcr.io/google-appengine/php

RUN apt-get update -y
RUN apt-get install -y libdmtx-utils