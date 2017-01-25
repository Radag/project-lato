import React from 'react';
import ReactDOM from 'react-dom';
import { createStore } from 'redux'; 
import App from './App';

/** Toto je náš reducer - volá se pomocí dispatch(action)
* @param Object state Aktuální state reduceru (současný stav)
* @param Object action Akce na kterou jsme zavolali dispatch() (data změny + identifikace akce)
* @return Object Vracíme nový stav
*/
var initialState = [];

function messageManager(state = initialState, action) { //zde využíváme 'defaultní hodnotu parametru' z ES6,
                                              // použije se při prvním zavolání reduceru, kdy je state undefined
  switch (action.type) {
    case 'ADD':
      var newState = [...state];
      newState.unshift(action.message);
      //využijeme Spread ES6 vlastnost a vložíme pole s aktuálním stavem reduceru
      // do nového pole s novým úkolem - změna je 'immutable'
      return newState;
    case 'ADD_LAST':
      return [...state, action.message];

    case 'REMOVE':
      //pomocí splice() odebereme prvek z pole, ktere jsem si nejprve zkopírovali
      // pomocí ES6 Spread direktivy
      console.log(action.idMessage);
      var ind = null;
      for(var i; i<state.length; i++) {
          if(state[i].id === action.idMessage) {
              ind = i;
          }
      }
      console.log(ind);
      
      var newState = [...state];
      newState.splice(ind, 1);
      return newState;

    default:
      //Vracíme stav objektu bezezměny,
      // akce patrně byla učena pro jiný reducer
      return state;
  }
}

// Vytvoříme si náš Store objekt s jediným reducerem.
// Můžeme na něm volat ouze 3 funkce:
// - subscribe, getState - pro zjištění stavu aplikace
// - dispatch - pro změnu stavu aplikace
var store = createStore(messageManager);

// Naše opravdu jednoduchá zobrazovací komponenta vypisuje do konzole a do stránky
// aktuální stav aplikace
store.subscribe(function() {
  var state = store.getState();
 // console.log("Nový stav:", state);
});

loadData();

// Toto je jediný způsob jak měnit stav aplikace. Objekt který vkládáme
// se jmenuje akce a obsahuje identifikaci a data potřebná pro provedení akce.
latoAddAfterStartMethod({
    submitClass: 'add-new-message-button',
    beginFunction: function(settings) {
        return $(settings.nette.ui).closest('div.modal');
    },
    doneFunction: function(data, beforeParam) {
        if(data.invalidForm === undefined || !data.invalidForm) {  
            store.dispatch({ type: 'ADD', message:data.message }); 
            beforeParam.closeModal();
        }
    }
});

latoAddAfterStartMethod({
    submitClass: 'delete-button',
        beginFunction: function(settings) {},
        doneFunction: function(data, beforeParam) {
            store.dispatch({ type: 'REMOVE', idMessage:data.idMessage });
        }
    });

function loadData()
{
    $.getJSON( $("#root").data('link'), function( data ) {
        $.each( data.messages, function( key, val ) {
            store.dispatch({ type: 'ADD_LAST', message:val }); //vložíme dva úkoly
        });
    });
}

function remove(idMessage)
{
    store.dispatch({ type: 'REMOVE', idMessage: idMessage});
}

ReactDOM.render(
    <App store={store} />, 
    document.getElementById("root")
);