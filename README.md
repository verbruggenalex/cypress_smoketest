# Cypress Smoketest

## Setup environment

```bash
# Wait 30 seconds or so because we set up the project in composer install.
docker-compose up -d
```

## Open cypress

```bash
# To start with x11 first allow connections by executing:
xhost +
# Then open your cypress project so you can manually run the tests.
docker-compose run --rm --entrypoint="cypress open --project ${PWD}" web
# To end with x11 make sure you put access control back on by executing:
xhost -
```

## Run cypress

```bash
# To just run the tests execute:
docker-compose run --rm --entrypoint="cypress run" web
```
