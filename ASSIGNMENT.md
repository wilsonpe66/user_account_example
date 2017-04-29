# Summary
Create a REST service that:
- Fetches user info from a database
- Calls another REST service to determine whether that user is active or suspended
- Returns the user info as JSON with the account status added (active/suspended)

# Requirements
- Must be implemented in accordance to OOP principles
- Must use REST principles for service API
- Must have functional test coverage of some type (e.g., unit, integration, etc)
- May use any programming language, frameworks, libs, etc as long as above
requirements are met
- May use any SQL database or Cassandra
- Not required to create the actual database--can assume an existing instance
  - Example table columns: user_id, email, address, zip_code
- Not required to create the downstream service--can assume an existing instance
  - Example downstream service
- If given user id is active, return JSON indicating as such
- If given user id is suspended, return JSON indicating as such
- If given user id doesn’t exist, return 404
