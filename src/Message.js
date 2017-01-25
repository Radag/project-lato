import React from 'react';

export default class Message extends React.Component {
  render() {
    return (
        <div className={'card grid-item flex-column' + this.props.isFirst}>  
            <div className="card-header">
                <a href="" className="avatar">
                    <img src={this.props.message.user.profileImage} alt={this.props.message.user.name} />
                </a>
                <h3 className="name truncate">
                    <a href="">{this.props.message.user.name} {this.props.message.user.surname}</a>
                </h3>
                <span className="card-date">{this.props.message.created}</span>
                <a className="btn-flat btn-icon dropdown-button card-options waves-effect waves-dark" data-activates={this.props.message.id + '_message_menu'} data-beloworigin="true" data-alignment="right" data-constraintwidth="false">
                    <i className="material-icons">more_vert</i>
                </a>
                <ul id={this.props.message.id + '_message_menu'} className='dropdown-content'>
                    <li><a href="" >Přesunout nahoru</a></li>
                    <li><a href="" >Zrušit přesunutí</a></li>
                    <li><a href="" >Znovu použít příspěvek</a></li>
                    <li className="divider"></li>
                    <li><a href="" >Upravit zprávu</a></li>
                    <li><a href={this.props.message.links.delete} className="ajax delete-button" >Smazat</a></li>
                </ul>
            </div>
            <div className="card-content">
                <p>{this.props.message.text}</p>
            </div>
        </div>
    );   
  }
};
