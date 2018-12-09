#!/bin/bash
docker-compose exec drupal bash -c "cd web && drush $@"
