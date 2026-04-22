# Basic Docker Information

This folder contains the Docker configuration for the Tahiche project.

## Container Management

In the `bin/` folder, there are scripts for removing, creating, and starting the necessary containers.

Once the containers are created, it may be necessary to run Composer to install dependencies.

### Accessing the Container

Running the following command allows you to access the container where the code is located:

```bash
docker exec -it tahiche_php bash
```

From within the container, you can run:

```bash
composer install
npm install && gulp build
```

## MySQL Execution

### To run MySQL with the user 'dbuser' (password 'dbuser')

```bash
mysql -h tahiche_db -P 3306 -u dbuser -p
```

### Managing the Database

You can remove the existing database and recreate it:

```sql
drop database tahiche;
create database tahiche;
use tahiche;
```

The database to be imported should be copied to the `tmp/` folder to make it available inside the container. If the file is named `tahiche_db.sql`:

```sql
source tmp/tahiche_db.sql;
```

---
*Spanish version available in [README_es.md](./README_es.md)*
