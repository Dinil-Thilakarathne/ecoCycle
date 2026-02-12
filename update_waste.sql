UPDATE pickup_request_wastes SET weight = quantity, amount = quantity * 10.00 WHERE weight IS NULL;
