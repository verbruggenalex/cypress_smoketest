/// <reference types="cypress" />
describe('dynamic users using request', () => {
  // this example fetches list of 3 users from the server
  // and then creates 3 separate tests to check something about each user

  let urls

  before(() => {
    // receive the dynamic list of users
    cy.request('http://web/cypress_smoketest/login/administrator')
      .then((response) => {
        urls = Object.values(response.body.data)
      })
  })

  // commands.js
  Cypress.Commands.add('preserveAllCookiesOnce', () => {
    cy.getCookies().then(cookies => {
      const namesOfCookies = cookies.map(c => c.name)
      Cypress.Cookies.preserveOnce(...namesOfCookies)
    })
  })

// your-suite.test.js
  beforeEach(function () {
    cy.preserveAllCookiesOnce()
  });

  // we know there will be 3 objects in the "users" list
  Cypress._.range(0, 7).forEach((k) => {
    it(`Visit # ${k}`, () => {
      const url = urls[k]
      cy.log(`Visiting ${url}`)
      cy.visit(`${url}`)
    })
  })
  // // dynamically create a single test for each operation in the list
  // urls.forEach((url) => {
  //   // derive test name from data
  //   it(`Visit ${url}`, () => {
  //     cy.visit(url)
  //   })
  // })

})
