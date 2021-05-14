describe('Smoketest', () => {
  it('Loop over retrieved routes.', () => {
    // Visit autologin endpoint and retrieve routes.
    cy.request('http://web/cypress_smoketest/login/administrator')
      .then((response) => {
        let urls = Object.values(response.body.data);
        // This loop should be put in its own it() function so we
        // Get better reporting of what url passed/failed.
        // @see: https://docs.cypress.io/guides/core-concepts/writing-and-organizing-tests#Dynamically-Generate-Tests
        // @note: it() can not be nested :(
        urls.forEach(function (url) {
          cy.visit(url)
        });
    })
  })
})
