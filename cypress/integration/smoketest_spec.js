describe('My First Test', () => {
  it('Visits the Kitchen Sink', () => {
    cy.request('http://web/cypress_smoketest/login/administrator')
      .then((response) => {
        response.body.data.forEach(function (url) {
          cy.visit(url)
        });
    })
  })
})
