class ApplicationController < ActionController::Base
  
  # Enforces https or ssl in application
  # Requires the use of the ssl_requirement plugin
  # See vendor/plugin/ssl_requirement for more information
  # Uncomment line below to enforce ssl connections for the website
  #include SslRequirement
  
#  before_filter :check_authentication, :except => [:signin]
#
#  def check_authentication
#    unless session[:user]
#      session[:intended_action] = action_name
#      session[:intended_controller] = controller_name      
#      redirect_to :action => 'signin',
#      :controller => 'account'
#    end
#  end

  private
#
#  def authorize
#    unless User.find_by_id(session[:user_id])
#      flash[:notice] = "Please log in"
#      redirect_to(:controller => "login", :action => "login")
#    end
#  end
  
end