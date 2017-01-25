import React from 'react';

//Import vnořené komponenty
import Message from './Message';


import Masonry from 'react-masonry-component';

export default class App extends React.Component {

  //uložíme data pro renderování do 'state', render se zavolá automaticky
  updateState() {
    this.setState({messages: this.props.store.getState()});
  }

  //okamžik v životním cyklu komponenty před prvním renderováním
  componentWillMount() {
    this.updateState() //úvodní načtení stavu
    this.props.store.subscribe(this.updateState.bind(this)); //aktualizace stavu
    
    
    
    
  }
  
  componentDidUpdate() {
        $('.dropdown-button').dropdown();
        $.nette.load();
  }

  render() {
    //vytvoříme pro každou todo položku její DOM vyjádření
    var messages = this.state.messages.map(function(message, id) {
        var isFirst = '';
        if(id===0) {
            isFirst = ' grid-item-fst';
        }
        //console.log(message);
        return <Message key={id} message={message} isFirst={isFirst} />
    });
    
    var AddButton = React.createClass({
        componentDidMount: function() {
            $("#addNewMessageButton").on("click", function() {
                 $('#select-post-type-modal').openModal();
            });
        },
        render: function() {
            return (
                <button id="addNewMessageButton" type="button" className="card modal-trigger create-post" >
                    <div className="avatar left">
                        <img src="" alt="" />
                    </div>
                    <div className="create-post-label left">
                        Chcete ostatním něco sdělit?
                    </div>
                </button>
            );
        }
    })
    
    var masonryOptions = {
        itemSelector: '.grid-item'
    };
    
//    var button = function() {
//        return <AddButton key="0" />
//    }
//
    //vykreslíme komponenty
    return <Masonry options={masonryOptions}>
            <AddButton />
            {messages}
           </Masonry>;
  }
};