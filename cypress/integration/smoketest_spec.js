describe('My First Test', () => {
  it('Visits the Kitchen Sink', () => {
    cy.request('http://web/cypress_smoketest/login/administrator')
      .then((response) => {
        let urls = Object.values(response.body.data);
        urls.forEach(function (url) {
          cy.visit(url)
        });
    })
  })
})
