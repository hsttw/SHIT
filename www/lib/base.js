var names = ['Alice', 'Emily', 'Kate'];
var title = ['fakeAP'];

var navbar = React.createClass({
    render: function() {
        return <div class="header item">
            {this.props.title}
        </div>;
    }
});

React.render(
    <navbar name="Cat University"></navbar>,
    $('#example')[0]
);