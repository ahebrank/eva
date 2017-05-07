The purpose of this directory is local replication of reported issues for EVA. 
It contains a Docker network and a composer-specified Drupal installation. The
EVA module is linked from the parent directory (i.e., local changes and patches
may be tested in real time).

To use:

- install Docker
- docker-compose up
- ./dcomposer install
- ./ddrush cim
- browse to http://localhost:9000
