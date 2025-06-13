Task description is provided in the task.pdf file.

How to run:
1. Clone repository.  
  ``git clone git@github.com:Yarcrazy/test_divlab.git``
2. Up docker:  
  ``docker compose up -d``
3. Install dependencies:  
  ``docker exec -it app composer install --working-dir=app``


Run tests:  
``docker exec -it app bash -c "cd app && ./vendor/bin/codecept run unit"``
